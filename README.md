# Yapay Zeka Destekli Sipariş Sistemi

Kullanıcı siparişini form doldurmak yerine yazarak veriyor (örneğin "2 adet siyah M beden basic tişört istiyorum, adresim ..."). Sistem mesajdan sipariş bilgilerini çıkarıyor, eksik olanları soruyor, kullanıcı onaylayınca siparişi kaydediyor. Siparişler admin panelinden takip ediliyor.

## Kullanılan teknolojiler

- **Backend:** Laravel 13 (PHP 8.4)
- **Yapay zeka servisi:** Python, FastAPI, Google Gemini (`google-genai`)
- **Veritabanı:** PostgreSQL 17
- **Arayüz:** React (Vite)
- Hepsi docker-compose ile çalışıyor.

## Kurulum

Gereken tek şey Docker (Docker Desktop) ve bir Gemini API anahtarı. Anahtar https://aistudio.google.com adresinden ücretsiz alınabiliyor.

1. Projeyi klonlayın.
2. Kök klasördeki `.env.example` dosyasını `.env` olarak kopyalayın ve `GEMINI_API_KEY` satırına anahtarınızı yazın.
3. Kök klasörde şu komutu çalıştırın:

```
docker compose up --build
```

İlk açılış birkaç dakika sürebilir. Backend açılırken composer paketlerini kuruyor, migration'ları ve seeder'ı kendisi çalıştırıyor. Ek bir komut gerekmiyor.

| Adres | Ne var |
|---|---|
| http://localhost:5173 | Sipariş (sohbet) ekranı |
| http://localhost:5173/admin | Admin paneli |
| http://localhost:8001/docs | Yapay zeka servisinin test sayfası |

**Admin girişi:** `admin@example.com` / `admin123`

Seeder 7 örnek ürün ekliyor. Ürünleri bazı durumları deneyebilmek için seçtim: iki tane tişört var ("tişört" yazınca hangisi diye soruluyor), şapkanın stoğu 3, bez çantanın stoğu 0.

Gemini modeli `.env` içindeki `GEMINI_MODEL` ile değiştirilebiliyor (varsayılan `gemini-2.5-flash`).

## Testler

```
docker compose exec backend php artisan test
```

Sipariş doğrulama (`tests/Feature/SiparisDogrulamaTest.php`) ve durum geçişleri (`tests/Feature/SiparisDurumTest.php`) için testler var. Testler bellekteki boş bir SQLite veritabanında çalışıyor, geliştirme veritabanına dokunmuyor.

## Nasıl çalışıyor

1. Kullanıcı mesaj yazıyor. React bunu Laravel'e gönderiyor.
2. Laravel mesajı, konuşma geçmişini ve mevcut sipariş taslağını Python servisine gönderiyor.
3. Python servisi Gemini'den taslağın güncel halini istiyor. Dönen cevabı Pydantic şemasıyla kontrol ediyor.
4. Laravel taslağı kontrol ediyor:
   - ürün adını kayıtlı ürünlerle eşleştiriyor
   - beden, renk, adet ve stoğa bakıyor
   - ad, telefon ve adres eksik mi diye bakıyor
   - fiyatları veritabanından alıp toplamı hesaplıyor
5. Eksik bir şey varsa kullanıcıya soruyor, yoksa özeti gösteriyor ve "Siparişi Onayla" butonu çıkıyor.
6. Onaylanınca sipariş backend'de tekrar doğrulanıp kaydediliyor ve kullanıcıya sipariş numarası gösteriliyor.

## Tasarım kararları

- **Yapay zeka sadece mesajdan bilgi çıkarıyor, kararları backend veriyor.** Ürün eşleştirme, stok, fiyat ve toplam hesabı Laravel'de yapılıyor. Gemini'nin döndürdüğü şemada fiyat alanı hiç yok. Fiyat her zaman veritabanından okunuyor.
- **Kullanıcıya giden cevapları (sorular, özet) yapay zeka yazmıyor.** Laravel'deki sabit metinlerle oluşturuluyor. Böylece sistem yanlış bir fiyat ya da olmayan bir ürün söyleyemiyor, davranışı test etmek de kolay oluyor.
- **Python servisi hiçbir şey kaydetmiyor.** Her istekte mevcut taslak, son mesajlar ve yeni mesaj gönderiliyor, servis taslağın güncel halini dönüyor. Konuşma ve taslak Laravel'de (veritabanında) tutuluyor.
- **Gemini her seferinde taslağın tamamını dönüyor.** "Aslında 3 tane olsun", "X'i iptal et, yerine Y ekle" gibi değişiklikler için ayrı ekle/sil komutları yazmadım. Eski taslak ve yeni mesaj veriliyor, taslağın son hali alınıyor.
- **Yapay zeka çıktısı iki kez kontrol ediliyor.** Gemini'ye şema veriliyor, dönen cevap ayrıca Pydantic ile doğrulanıyor. Uymazsa bir kez daha deneniyor. Yine olmazsa kullanıcıdan mesajı tekrar yazması isteniyor ve mevcut taslak bozulmuyor.
- **Ürün eşleştirme basit ve açıklanabilir bir yöntemle yapılıyor.** Metin sadeleştiriliyor (küçük harf, Türkçe karakterler, noktalama), sonra kelime kelime karşılaştırılıyor. Yazım hataları için en fazla 2 harf farkına izin veriliyor (`levenshtein`). Birden fazla ürün eşleşirse kullanıcıya hangisini istediği soruluyor.
- **Sayfa yenilenince konuşma kaybolmuyor.** Tarayıcıda (`localStorage`) sadece konuşmanın ID'si tutuluyor. Mesajlar ve taslak veritabanında, sayfa açılınca oradan yükleniyor.
- **Sipariş kaydedilirken her şey yapay zekadan bağımsız olarak tekrar doğrulanıyor** (`SiparisServisi`): ürün var mı, adet, beden ve renk geçerli mi, telefon formatı doğru mu, stok yetiyor mu. Stok kontrolünde aynı ürünün farklı satırlardaki toplam adedine bakılıyor. Kayıt transaction içinde yapılıyor ve stok düşülüyor.
- **Aynı onay iki kez gelirse mükerrer sipariş oluşmuyor.** Üç yerde önlem var:
  1. Controller önce o konuşmanın siparişi var mı diye bakıyor, varsa onu dönüyor.
  2. Aynı anda gelen istekler için konuşma satırı kilitleniyor (`lockForUpdate`).
  3. Veritabanında `siparisler.konusma_id` unique.
- **Durum geçişleri `Siparis` modelinde tek bir dizide tanımlı.** beklemede → hazirlaniyor → kargoda → teslim_edildi. Beklemede ve hazırlanıyor durumundaki sipariş iptal edilebiliyor. Teslim edilmiş ya da iptal edilmiş siparişin durumu değiştirilemiyor. Kural backend'de kontrol ediliyor, ekran sadece izin verilen durumları gösteriyor.
- **Admin paneli Sanctum token ile korunuyor.** Giriş yapmadan admin API'si 401 dönüyor. Giriş adresinde dakikada 5 deneme sınırı var.
- **Testler SQLite'ta çalışıyor.** docker-compose veritabanı ayarlarını ortam değişkeni olarak verdiği için `phpunit.xml`'de `<server ... force="true">` kullandım. Yoksa testler geliştirme veritabanına bağlanıp verileri siliyordu.
- Kod içindeki sınıf, fonksiyon ve tablo isimlerini Türkçe (Türkçe karakter olmadan) yazdım.

## Bilinen eksikler

- İptal edilen siparişin ürünleri stoğa geri eklenmiyor.
- Telefon olarak sadece cep telefonu (05XX) kabul ediliyor.
- Ürün eşleştirme anlamsal değil. Örneğin "t-shirt" ile "tişört"ü bağlayamıyor. Ayrıca her aramada bütün ürünler geziliyor, çok büyük bir katalogda yavaş kalır.
- Aynı ürün farklı satırlarda istenince (ör. 2 siyah + 2 beyaz tişört) toplam adedin stoğu aşması sohbet sırasında değil, onay sırasında yakalanıyor.
- Admin listesinde sayfalama yok. Tek admin kullanıcısı var, rol yetkilendirmesi yok.
- Yapay zeka bazen mesajı yanlış anlayabilir. Kullanıcı özeti görüp düzeltebiliyor ama bunu tamamen engellemiyor.
- Yapay zeka servisi için otomatik test yok.
- docker-compose geliştirme ortamı için hazırlandı (`php artisan serve`, Vite dev server). Production için ayrı bir kurulum gerekir.