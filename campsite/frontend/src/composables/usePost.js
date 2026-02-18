import { ref } from 'vue'
import api from '../api/axios'

const stored = localStorage.getItem('post')
const post = ref(stored ? JSON.parse(stored) : null)
const loading = ref(false)

const fetchPost = async (params) => {
  loading.value = true

  try {
    const response = await api.get('/post', {
      params
    })
    post.value = response.data
    localStorage.setItem('post', JSON.stringify(post.value))
    return post.value
  } catch (error) {
    console.error('Post fetch error:', error)
    throw error
  } finally {
    loading.value = false
  }
}

export function usePost() {
  return {
    post,
    loading,
    fetchPost
  }
}