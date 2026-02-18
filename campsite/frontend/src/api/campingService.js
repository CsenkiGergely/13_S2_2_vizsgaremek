import api from './axios'


// --- GeoJSON térkép ---

// GeoJSON lekérése kempinghez (publikus)
export function getGeojson(campingId) {
  return api.get('/campings/' + campingId + '/geojson')
}

// GeoJSON feltöltése kempinghez (tulajdonos - geojson.io-ból exportált fájl)
export function uploadGeojson(campingId, geojson) {
  return api.post('/campings/' + campingId + '/geojson', { geojson })
}

// GeoJSON törlése
export function deleteGeojson(campingId) {
  return api.delete('/campings/' + campingId + '/geojson')
}
