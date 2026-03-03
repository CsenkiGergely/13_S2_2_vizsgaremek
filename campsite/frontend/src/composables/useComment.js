import { ref } from 'vue'
import api from '../api/axios'

const comments = ref([])
const loading = ref(false)
const error = ref(null)

const getErrorMessage = (err) => {
  return err.response?.data?.message || err.message
}

const normalizeComment = (comment) => {
  if (!comment || typeof comment !== 'object') {
    return comment
  }

  const rawReplies = Array.isArray(comment.replies)
    ? comment.replies
    : Array.isArray(comment.children)
      ? comment.children
      : []

  const normalizedReplies = rawReplies.map(normalizeComment)
  const normalizedComment = {
    ...comment,
    replies: normalizedReplies
  }

  if ('children' in normalizedComment) {
    delete normalizedComment.children
  }

  return normalizedComment
}

const normalizeCommentList = (items) => {
  return Array.isArray(items) ? items.map(normalizeComment) : []
}

const addReplyToComments = (items, parentCommentId, newReply) => {
  return items.map((item) => {
    if (item.id === parentCommentId) {
      const existingReplies = Array.isArray(item.replies) ? item.replies : []
      return {
        ...item,
        replies: [...existingReplies, normalizeComment(newReply)]
      }
    }

    const updatedItem = { ...item }

    if (Array.isArray(item.replies) && item.replies.length > 0) {
      updatedItem.replies = addReplyToComments(item.replies, parentCommentId, newReply)
    }

    return updatedItem
  })
}

// Kemping kommentjeinek lekérése
const getComments = async (campingId) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get(`/campings/${campingId}/comments`)
    comments.value = normalizeCommentList(response.data.data || response.data)
    return comments.value
  } catch (err) {
    console.error('Hiba a kommentek lekérésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Új komment hozzáadása
const addComment = async (campingId, data) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.post(`/campings/${campingId}/comments`, data)
    const createdComment = normalizeComment(response.data.data || response.data)
    comments.value = [...comments.value, createdComment]
    return createdComment
  } catch (err) {
    console.error('Hiba a komment hozzáadásakor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Komment frissítése
const updateComment = async (commentId, data) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.put(`/comments/${commentId}`, data)
    const updatedComment = normalizeComment(response.data.data || response.data)
    comments.value = comments.value.map((comment) =>
      comment.id === commentId ? { ...comment, ...updatedComment } : comment
    )
    return updatedComment
  } catch (err) {
    console.error('Hiba a komment frissítésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Válasz kommentre
const replyComment = async (commentId, data) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.post(`/comments/${commentId}/reply`, data)
    const createdReply = normalizeComment(response.data.data || response.data)
    comments.value = addReplyToComments(comments.value, commentId, createdReply)
    return createdReply
  } catch (err) {
    console.error('Hiba a kommentre adott válasz mentésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Komment törlése
const deleteComment = async (commentId) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.delete(`/comments/${commentId}`)
    comments.value = comments.value.filter((comment) => comment.id !== commentId)
    return response.data
  } catch (err) {
    console.error('Hiba a komment törlésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

export function useComment() {
  return {
    comments,
    loading,
    error,
    getComments,
    addComment,
    updateComment,
    replyComment,
    deleteComment
  }
}