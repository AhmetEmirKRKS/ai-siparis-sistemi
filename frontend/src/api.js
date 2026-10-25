export async function adminIstegi(adres, ayarlar = {}) {
  const cevap = await fetch('/api/admin' + adres, {
    ...ayarlar,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Authorization: 'Bearer ' + localStorage.getItem('adminToken'),
    },
  })

  // token geçersizse tekrar giriş ekranına dön
  if (cevap.status === 401) {
    localStorage.removeItem('adminToken')
    window.location.reload()
  }

  const veri = await cevap.json()

  if (!cevap.ok) {
    throw new Error(veri.message || 'Bir hata oluştu')
  }

  return veri
}