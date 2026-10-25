import { useState } from 'react'
import { adminIstegi } from './api'
import SiparisDetay from './SiparisDetay'
import SiparisListesi from './SiparisListesi'

function Admin() {
  const [token, setToken] = useState(localStorage.getItem('adminToken'))
  const [email, setEmail] = useState('')
  const [sifre, setSifre] = useState('')
  const [hata, setHata] = useState('')
  const [seciliId, setSeciliId] = useState(null)

  async function girisYap(e) {
    e.preventDefault()
    setHata('')

    const cevap = await fetch('/api/admin/giris', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({ email: email, password: sifre }),
    })
    const veri = await cevap.json()

    if (!cevap.ok) {
      setHata(veri.message)
      return
    }

    localStorage.setItem('adminToken', veri.token)
    setToken(veri.token)
  }

  async function cikisYap() {
    await adminIstegi('/cikis', { method: 'POST' })
    localStorage.removeItem('adminToken')
    setToken(null)
  }

  if (!token) {
    return (
      <div className="admin">
        <h1>Admin Girişi</h1>
        <form className="giris" onSubmit={girisYap}>
          <input type="email" placeholder="E-posta" value={email} onChange={(e) => setEmail(e.target.value)} />
          <input type="password" placeholder="Şifre" value={sifre} onChange={(e) => setSifre(e.target.value)} />
          <button type="submit">Giriş Yap</button>
          {hata && <p className="hata">{hata}</p>}
        </form>
      </div>
    )
  }

  return (
    <div className="admin">
      <div className="ust">
        <h1>Siparişler</h1>
        <button onClick={cikisYap}>Çıkış Yap</button>
      </div>

      {seciliId ? (
        <SiparisDetay id={seciliId} geriDon={() => setSeciliId(null)} />
      ) : (
        <SiparisListesi siparisSec={setSeciliId} />
      )}
    </div>
  )
}

export default Admin