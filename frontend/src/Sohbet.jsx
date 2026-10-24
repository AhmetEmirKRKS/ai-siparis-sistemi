import { useEffect, useState } from 'react'

function Sohbet() {
  const [konusma, setKonusma] = useState(null)
  const [mesaj, setMesaj] = useState('')
  const [yukleniyor, setYukleniyor] = useState(false)

  useEffect(() => {
    konusmayiYukle()
  }, [])

  async function konusmayiYukle() {
    const kayitliId = localStorage.getItem('konusmaId')

    if (kayitliId) {
      const cevap = await fetch('/api/konusmalar/' + kayitliId)
      if (cevap.ok) {
        setKonusma(await cevap.json())
        return
      }
    }

    yeniKonusmaBaslat()
  }

  async function yeniKonusmaBaslat() {
    const cevap = await fetch('/api/konusmalar', { method: 'POST' })
    const veri = await cevap.json()
    localStorage.setItem('konusmaId', veri.id)
    setKonusma(veri)
  }

  async function mesajGonder(e) {
    e.preventDefault()
    if (mesaj.trim() === '') return

    const gonderilen = mesaj
    setMesaj('')
    setYukleniyor(true)

    // cevap gelene kadar kullanıcının mesajı ekranda görünsün
    setKonusma({
      ...konusma,
      mesajlar: [...konusma.mesajlar, { rol: 'kullanici', icerik: gonderilen }],
    })

    try {
      const cevap = await fetch('/api/konusmalar/' + konusma.id + '/mesajlar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ mesaj: gonderilen }),
      })
      if (!cevap.ok) throw new Error()
      setKonusma(await cevap.json())
    } catch {
      alert('Mesaj gönderilemedi, lütfen tekrar deneyin.')
      konusmayiYukle()
    }

    setYukleniyor(false)
  }

  async function siparisiOnayla() {
    setYukleniyor(true)

    try {
      const cevap = await fetch('/api/konusmalar/' + konusma.id + '/onayla', {
        method: 'POST',
        headers: { Accept: 'application/json' },
      })
      if (!cevap.ok) throw new Error()
      setKonusma(await cevap.json())
    } catch {
      alert('Sipariş onaylanamadı, lütfen tekrar deneyin.')
    }

    setYukleniyor(false)
  }

  function yeniSiparis() {
    localStorage.removeItem('konusmaId')
    setKonusma(null)
    yeniKonusmaBaslat()
  }

  if (!konusma) {
    return <p>Yükleniyor...</p>
  }

  return (
    <div className="sohbet">
      <h1>Sipariş Asistanı</h1>

      <div className="mesajlar">
        {konusma.mesajlar.length === 0 && (
          <p className="bilgi">
            Merhaba! Siparişinizi yazabilirsiniz. Örnek: "2 adet siyah M beden basic tişört istiyorum,
            adım Ahmet Yılmaz, telefonum 0532 123 45 67, adresim ..."
          </p>
        )}

        {konusma.mesajlar.map((m, index) => (
          <div key={index} className={'mesaj ' + m.rol}>
            {m.icerik}
          </div>
        ))}

        {yukleniyor && <div className="mesaj asistan">Yazıyor...</div>}
      </div>

      {konusma.siparis_no ? (
        <div className="tamamlandi">
          <p>
            Siparişiniz alındı. Sipariş numaranız: <b>{konusma.siparis_no}</b>
          </p>
          <button onClick={yeniSiparis}>Yeni Sipariş</button>
        </div>
      ) : (
        <>
          {konusma.onaya_hazir && (
            <button className="onayla" onClick={siparisiOnayla} disabled={yukleniyor}>
              Siparişi Onayla
            </button>
          )}

          <form onSubmit={mesajGonder}>
            <input
              value={mesaj}
              onChange={(e) => setMesaj(e.target.value)}
              placeholder="Mesajınızı yazın..."
              disabled={yukleniyor}
            />
            <button type="submit" disabled={yukleniyor}>
              Gönder
            </button>
          </form>
        </>
      )}
    </div>
  )
}

export default Sohbet