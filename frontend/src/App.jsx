import Admin from './Admin'
import Sohbet from './Sohbet'

function App() {
  if (window.location.pathname.startsWith('/admin')) {
    return <Admin />
  }

  return <Sohbet />
}

export default App