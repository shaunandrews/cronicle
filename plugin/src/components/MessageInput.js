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
  const [showOptions, setShowOptions] = useState(false);
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
          mode: state.selectedMode || 'draft',
          template: state.selectedTemplate || 'auto',
          context_providers: state.enabledContextProviders
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

  const handleModeChange = (e) => {
    dispatch({ type: ACTIONS.SET_MODE, payload: e.target.value });
  };

  const handleTemplateChange = (e) => {
    dispatch({ type: ACTIONS.SET_TEMPLATE, payload: e.target.value });
  };

  const handleContextProviderChange = (provider) => (e) => {
    dispatch({ 
      type: ACTIONS.SET_CONTEXT_PROVIDER, 
      payload: { provider, enabled: e.target.checked }
    });
  };

  return (
    <div className="cronicle-input-area">
      {/* Options Controls */}
      <div className="cronicle-input-options">
        <div className="cronicle-input-options-main">
          {/* Mode Selector */}
          <div className="cronicle-option-group">
            <label htmlFor="cronicle-input-mode">
              {__('Mode:', 'cronicle')}
            </label>
            <select 
              id="cronicle-input-mode" 
              className="cronicle-input-option-select"
              value={state.selectedMode || 'draft'}
              onChange={handleModeChange}
            >
              <option value="draft">{__('Full Draft', 'cronicle')}</option>
              <option value="outline">{__('Outline', 'cronicle')}</option>
            </select>
          </div>

          {/* Template Selector */}
          <div className="cronicle-option-group">
            <label htmlFor="cronicle-input-template">
              {__('Template:', 'cronicle')}
            </label>
            <select 
              id="cronicle-input-template" 
              className="cronicle-input-option-select"
              value={state.selectedTemplate || 'auto'}
              onChange={handleTemplateChange}
            >
              <option value="auto">{__('Auto', 'cronicle')}</option>
              <option value="blog-post-professional">{__('Professional', 'cronicle')}</option>
              <option value="blog-post-casual">{__('Casual', 'cronicle')}</option>
              <option value="content-outline">{__('Outline', 'cronicle')}</option>
              <option value="how-to-guide">{__('How-to', 'cronicle')}</option>
              <option value="listicle">{__('List', 'cronicle')}</option>
            </select>
          </div>

          {/* Options Toggle */}
          <button 
            type="button" 
            className="button button-small cronicle-options-toggle"
            onClick={() => setShowOptions(!showOptions)}
          >
            {showOptions ? __('Less', 'cronicle') : __('More', 'cronicle')}
          </button>
        </div>

        {/* Expandable Context Options */}
        {showOptions && (
          <div className="cronicle-input-context-options">
            <span className="cronicle-context-label">{__('Include:', 'cronicle')}</span>
            <div className="cronicle-context-checkboxes-inline">
              {Object.entries(state.enabledContextProviders || {}).map(([provider, enabled]) => (
                <label key={provider} className="cronicle-context-checkbox-inline">
                  <input
                    type="checkbox"
                    checked={enabled}
                    onChange={handleContextProviderChange(provider)}
                  />
                  <span>
                    {provider === 'writing_style' ? __('Style', 'cronicle') :
                     provider === 'conversation' ? __('Chat', 'cronicle') :
                     __(provider.charAt(0).toUpperCase() + provider.slice(1), 'cronicle')}
                  </span>
                </label>
              ))}
            </div>
          </div>
        )}
      </div>

      {/* Input Form */}
      <form className="cronicle-input-form" onSubmit={handleSubmit}>
        <textarea
          ref={textareaRef}
          className="cronicle-input"
          value={inputValue}
          onChange={handleInputChange}
          onKeyDown={handleKeyDown}
          placeholder={state.selectedMode === 'outline' 
            ? __('What topic would you like an outline for?', 'cronicle')
            : __('What would you like to write about?', 'cronicle')
          }
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