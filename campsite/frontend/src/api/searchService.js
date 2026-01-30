import api from './axios'

export const searchCampings = async (searchParams) => {
  const response = await api.get('/booking/search', {
    params: {
      location: searchParams.location,
      arrival_date: searchParams.checkIn,
      departure_date: searchParams.checkOut,
      guests: searchParams.guests,
      page: searchParams.page || 1
    }
  })
  return response.data
}
