/**
 * Message Input Component - Auto-growing textarea with send functionality
 */

import { useState, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useCronicle } from '../context/CronicleContext';
import { ACTIONS } from '../context/CronicleContext';
import { sendChatMessage, reviseDraft } from '../utils/api';

const MessageInput = () => {
  const { state, dispatch } = useCronicle();
  const [inputValue, setInputValue] = useState('');
  const textareaRef = useRef(null);

  const maxHeight = 300;

  const resizeTextarea = () => {
    const textarea = textareaRef.current;
    if (!textarea) return;

    textarea.style.height = 'auto';
    
    let scrollHeight = textarea.scrollHeight;
    
    // Handle placeholder height calculation when empty
    if (!inputValue) {
      const tempDiv = document.createElement('div');
      const computedStyle = window.getComputedStyle(textarea);
      
      tempDiv.style.position = 'absolute';
      tempDiv.style.visibility = 'hidden';
      tempDiv.style.padding = computedStyle.padding;
      tempDiv.style.width = computedStyle.width;
      tempDiv.style.fontFamily = computedStyle.fontFamily;
      tempDiv.style.fontSize = computedStyle.fontSize;
      tempDiv.style.lineHeight = computedStyle.lineHeight;
      tempDiv.style.whiteSpace = 'pre-wrap';
      tempDiv.style.wordWrap = 'break-word';
      tempDiv.textContent = textarea.placeholder;
      
      document.body.appendChild(tempDiv);
      scrollHeight = tempDiv.scrollHeight;
      document.body.removeChild(tempDiv);
    }

    const newHeight = Math.min(scrollHeight, maxHeight);
    textarea.style.height = `${newHeight}px`;
    textarea.style.overflowY = scrollHeight > maxHeight ? 'auto' : 'hidden';
  };

  useEffect(() => {
    resizeTextarea();
  }, [inputValue]);

  useEffect(() => {
    // Update placeholder based on selected mode
    if (textareaRef.current) {
      const placeholder = state.selectedMode === 'outline'
        ? __('What topic would you like an outline for? (e.g., benefits of exercise, cooking tips, travel destinations)', 'cronicle')
        : __('What would you like to write about? (e.g., benefits of exercise, cooking tips, travel destinations)', 'cronicle');
      
      textareaRef.current.placeholder = placeholder;
      resizeTextarea();
    }
  }, [state.selectedMode]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    const message = inputValue.trim();
    if (!message || state.isSending) return;

    // Add user message to chat
    dispatch({ 
      type: ACTIONS.ADD_MESSAGE, 
      payload: { 
        type: 'user', 
        content: message, 
        timestamp: new Date().getTime() 
      }
    });

    // Clear input and set sending state
    setInputValue('');
    dispatch({ type: ACTIONS.SET_SENDING, payload: true });
    dispatch({ type: ACTIONS.SET_TYPING, payload: true });

    try {
      let response;
      
      if (state.currentDraft) {
        // Revise existing draft
        response = await reviseDraft({
          title: state.currentDraft.title,
          content: state.currentDraft.content,
          instructions: message
        });
      } else {
        // Send new chat message
        response = await sendChatMessage({
          message: message,
          mode: state.selectedMode || 'draft'
        });
      }

      if (response.success && response.data.content) {
        // Add assistant response
        const messageData = {
          type: 'assistant',
          content: response.data.content,
          timestamp: new Date().getTime(),
          is_post_content: response.data.is_post_content || false,
          post_data: response.data.post_data || null
        };

        dispatch({ type: ACTIONS.ADD_MESSAGE, payload: messageData });

        // Update current draft if post content was generated
        if (response.data.is_post_content && response.data.post_data) {
          dispatch({ type: ACTIONS.SET_CURRENT_DRAFT, payload: response.data.post_data });
        }
      } else {
        const errorMsg = response.data?.message || __('Error sending message. Please try again.', 'cronicle');
        dispatch({ 
          type: ACTIONS.ADD_MESSAGE, 
          payload: { 
            type: 'assistant', 
            content: `Error: ${errorMsg}`, 
            timestamp: new Date().getTime() 
          }
        });
      }
    } catch (error) {
      dispatch({ 
        type: ACTIONS.ADD_MESSAGE, 
        payload: { 
          type: 'assistant', 
          content: `Error: ${__('Error sending message. Please try again.', 'cronicle')}`, 
          timestamp: new Date().getTime() 
        }
      });
    } finally {
      dispatch({ type: ACTIONS.SET_SENDING, payload: false });
      dispatch({ type: ACTIONS.SET_TYPING, payload: false });
      
      // Focus back to input
      if (textareaRef.current) {
        textareaRef.current.focus();
      }
    }
  };

  const handleKeyDown = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSubmit(e);
    }
  };

  const handleInputChange = (e) => {
    setInputValue(e.target.value);
  };

  return (
    <div className="cronicle-input-area">
      <form className="cronicle-input-form" onSubmit={handleSubmit}>
        <textarea
          ref={textareaRef}
          className="cronicle-input"
          value={inputValue}
          onChange={handleInputChange}
          onKeyDown={handleKeyDown}
          placeholder={__('What would you like to write about? (e.g., benefits of exercise, cooking tips, travel destinations)', 'cronicle')}
          rows="1"
        />
        <button 
          type="submit" 
          className="cronicle-send-button"
          disabled={state.isSending}
        >
          {state.isSending ? __('Sending...', 'cronicle') : __('Send', 'cronicle')}
        </button>
      </form>
    </div>
  );
};

export default MessageInput;