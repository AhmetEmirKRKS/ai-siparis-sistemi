import { useEffect, useState } from 'react'
import { adminIstegi } from './api'

function SiparisDetay({ id, geriDon }) {
  const [veri, setVeri] = useState(null)
  const [yeniDurum, setYeniDurum] = useState('')

  useEffect(() => {
    adminIstegi('/siparisler/' + id)
      .then((cevap) => setVeri(cevap))
      .catch((hata) => alert(hata.message))
  }, [id])

  async function durumuGuncelle() {
    try {
      const cevap = await adminIstegi('/siparisler/' + id + '/durum', {
        method: 'PATCH',
        body: JSON.stringify({ durum: yeniDurum }),
      })
      setVeri(cevap)
      setYeniDurum('')
    } catch (hata) {
      alert(hata.message)
    }
  }

  if (!veri) {
    return <p>Yükleniyor...</p>
  }

  const siparis = veri.siparis

  return (
    <div>
      <button onClick={geriDon}>← Listeye dön</button>

      <h2>{siparis.siparis_no}</h2>
      <p><b>Durum:</b> {siparis.durum}</p>
      <p><b>Müşteri:</b> {siparis.ad_soyad}</p>
      <p><b>Telefon:</b> {siparis.telefon}</p>
      <p><b>Adres:</b> {siparis.adres}</p>
      <p><b>Tarih:</b> {new Date(siparis.created_at).toLocaleString('tr-TR')}</p>

      <h3>Ürünler</h3>
      <table>
        <thead>
          <tr>
            <th>Ürün</th>
            <th>Seçenekler</th>
            <th>Adet</th>
            <th>Birim Fiyat</th>
            <th>Tutar</th>
          </tr>
        </thead>
        <tbody>
          {siparis.kalemler.map((k) => (
            <tr key={k.id}>
              <td>{k.urun_adi}</td>
              <td>
                {k.secenekler
                  ? Object.entries(k.secenekler).map(([ad, deger]) => ad + ': ' + deger).join(', ')
                  : '-'}
              </td>
              <td>{k.adet}</td>
              <td>{k.birim_fiyat} TL</td>
              <td>{k.tutar} TL</td>
            </tr>
          ))}
        </tbody>
      </table>
      <p><b>Toplam: {siparis.toplam} TL</b></p>

      <h3>Durum Güncelle</h3>
      {veri.sonraki_durumlar.length === 0 ? (
        <p>Bu siparişin durumu artık değiştirilemez.</p>
      ) : (
        <div className="filtreler">
          <select value={yeniDurum} onChange={(e) => setYeniDurum(e.target.value)}>
            <option value="">Yeni durum seçin</option>
            {veri.sonraki_durumlar.map((d) => (
              <option key={d} value={d}>
                {d}
              </option>
            ))}
          </select>
          <button onClick={durumuGuncelle} disabled={!yeniDurum}>
            Güncelle
          </button>
        </div>
      )}

      <h3>Siparişin Oluştuğu Konuşma</h3>
      <div className="mesajlar">
        {siparis.konusma.mesajlar.map((m) => (
          <div key={m.id} className={'mesaj ' + m.rol}>
            {m.icerik}
          </div>
        ))}
      </div>
    </div>
  )
}

export default SiparisDetay