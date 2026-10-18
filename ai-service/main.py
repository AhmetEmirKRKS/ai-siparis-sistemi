import logging
import os

from fastapi import FastAPI, HTTPException
from google import genai
from google.genai import types
from pydantic import BaseModel, Field, ValidationError

app = FastAPI()
client = genai.Client()
logger = logging.getLogger("uvicorn.error")

MODEL = os.getenv("GEMINI_MODEL", "gemini-2.5-flash")

TALIMAT = """Sen bir online mağazanın sipariş asistanısın. Görevin kullanıcının yazdığı mesajlardan sipariş bilgilerini çıkarmak.

Sana mevcut sipariş taslağı, konuşma geçmişi ve kullanıcının yeni mesajı verilecek. Yeni mesaja göre taslağın güncel ve eksiksiz halini döndür.

Kurallar:
- Ürün adını kullanıcının yazdığı gibi yaz, düzeltmeye çalışma.
- Asistan "hangisini kastettiniz" diye seçenek sunduysa ve kullanıcı birini seçtiyse, ürün adını sunulan seçenekteki haliyle yaz.
- Adet belirtilmemişse adet alanını null bırak.
- Beden, renk gibi özellikleri secenekler listesine yaz. Seçenek adını küçük harfle yaz (örneğin "beden", "renk").
- Aynı ürün farklı seçeneklerle istenirse ayrı kalemler olarak yaz.
- Kullanıcı daha önce verdiği bir bilgiyi değiştirirse yeni değeri yaz.
- Kullanıcı bir ürünü iptal ederse o ürünü kalemlerden çıkar.
- Yeni mesajda değişmeyen bilgileri taslaktan aynen koru.
- Mesajda olmayan bir bilgiyi uydurma.
- Fiyat veya toplam tutar hesaplama."""


class Secenek(BaseModel):
    ad: str
    deger: str


class Kalem(BaseModel):
    urun: str
    adet: int | None = Field(default=None, ge=1)
    secenekler: list[Secenek] = []


class Taslak(BaseModel):
    kalemler: list[Kalem] = []
    ad_soyad: str | None = None
    telefon: str | None = None
    adres: str | None = None


class GecmisMesaj(BaseModel):
    rol: str
    icerik: str


class CikarmaIstegi(BaseModel):
    taslak: Taslak | None = None
    gecmis: list[GecmisMesaj] = []
    mesaj: str


def istem_olustur(istek: CikarmaIstegi) -> str:
    taslak = istek.taslak.model_dump_json() if istek.taslak else "{}"
    gecmis = "\n".join(f"{m.rol}: {m.icerik}" for m in istek.gecmis) or "(boş)"

    return (
        f"Mevcut taslak:\n{taslak}\n\n"
        f"Konuşma geçmişi:\n{gecmis}\n\n"
        f"Kullanıcının yeni mesajı:\n{istek.mesaj}"
    )


@app.get("/health")
def health():
    return {"status": "ok"}


@app.post("/cikar", response_model=Taslak)
def cikar(istek: CikarmaIstegi):
    istem = istem_olustur(istek)

    for deneme in range(2):
        try:
            cevap = client.models.generate_content(
                model=MODEL,
                contents=istem,
                config=types.GenerateContentConfig(
                    system_instruction=TALIMAT,
                    response_mime_type="application/json",
                    response_schema=Taslak,
                    temperature=0,
                ),
            )
        except Exception as hata:
            logger.error("Gemini istegi basarisiz: %s", hata)
            raise HTTPException(status_code=502, detail="Yapay zeka servisine ulaşılamadı")

        try:
            return Taslak.model_validate_json(cevap.text)
        except ValidationError as hata:
            logger.warning("Gecersiz cikti (deneme %s): %s", deneme + 1, hata)

    raise HTTPException(status_code=422, detail="Yapay zeka çıktısı beklenen formatta değil")