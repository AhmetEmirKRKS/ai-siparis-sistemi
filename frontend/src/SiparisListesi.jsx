import { useEffect, useState } from 'react'
import { adminIstegi } from './api'

const DURUMLAR = ['beklemede', 'hazirlaniyor', 'kargoda', 'teslim_edildi', 'iptal_edildi']

function SiparisListesi({ siparisSec }) {
  const [siparisler, setSiparisler] = useState([])
  const [durum, setDurum] = useState('')
  const [baslangic, setBaslangic] = useState('')
  const [bitis, setBitis] = useState('')

  useEffect(() => {
    listele()
  }, [])

  async function listele() {
    const filtreler = new URLSearchParams()
    if (durum) filtreler.append('durum', durum)
    if (baslangic) filtreler.append('baslangic', baslangic)
    if (bitis) filtreler.append('bitis', bitis)

    try {
      setSiparisler(await adminIstegi('/siparisler?' + filtreler.toString()))
    } catch (hata) {
      alert(hata.message)
    }
  }

  return (
    <div>
      <div className="filtreler">
        <select value={durum} onChange={(e) => setDurum(e.target.value)}>
          <option value="">Tüm durumlar</option>
          {DURUMLAR.map((d) => (
            <option key={d} value={d}>
              {d}
            </option>
          ))}
        </select>
        <input type="date" value={baslangic} onChange={(e) => setBaslangic(e.target.value)} />
        <input type="date" value={bitis} onChange={(e) => setBitis(e.target.value)} />
        <button onClick={listele}>Filtrele</button>
      </div>

      <table className="liste">
        <thead>
          <tr>
            <th>Sipariş No</th>
            <th>Müşteri</th>
            <th>Toplam</th>
            <th>Durum</th>
            <th>Tarih</th>
          </tr>
        </thead>
        <tbody>
          {siparisler.map((s) => (
            <tr key={s.id} onClick={() => siparisSec(s.id)}>
              <td>{s.siparis_no}</td>
              <td>{s.ad_soyad}</td>
              <td>{s.toplam} TL</td>
              <td>{s.durum}</td>
              <td>{new Date(s.created_at).toLocaleString('tr-TR')}</td>
            </tr>
          ))}
        </tbody>
      </table>

      {siparisler.length === 0 && <p>Sipariş bulunamadı.</p>}
    </div>
  )
}

export default SiparisListesi