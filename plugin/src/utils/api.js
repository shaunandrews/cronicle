/**
 * API Utility Functions for WordPress AJAX requests
 */

// Helper function to make AJAX requests
const makeAjaxRequest = async (data) => {
  const response = await fetch(window.cronicle_ajax.ajax_url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
      ...data,
      nonce: window.cronicle_ajax.nonce
    })
  });

  return response.json();
};

// Send chat message
export const sendChatMessage = async ({ message, mode }) => {
  return makeAjaxRequest({
    action: 'cronicle_chat_message',
    message,
    mode
  });
};

// Revise existing draft
export const reviseDraft = async ({ title, content, instructions }) => {
  return makeAjaxRequest({
    action: 'cronicle_revise_draft',
    title,
    content,
    instructions
  });
};

// Create post/draft
export const createPost = async ({ title, content }) => {
  return makeAjaxRequest({
    action: 'cronicle_create_post',
    title,
    content
  });
};

// Publish post
export const publishPost = async ({ title, content }) => {
  return makeAjaxRequest({
    action: 'cronicle_publish_post',
    title,
    content
  });
};

// Schedule post
export const schedulePost = async ({ title, content, scheduled_datetime }) => {
  return makeAjaxRequest({
    action: 'cronicle_schedule_post',
    title,
    content,
    scheduled_datetime
  });
};

// Load chat history
export const loadChatHistory = async () => {
  return makeAjaxRequest({
    action: 'cronicle_load_chat_history'
  });
};

// Start new session
export const startNewSession = async () => {
  return makeAjaxRequest({
    action: 'cronicle_start_new_session'
  });
};