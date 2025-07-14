/**
 * Session Dropdown Component
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useCronicle } from '../context/CronicleContext';
import { ACTIONS } from '../context/CronicleContext';
import { loadSessionList, switchSession, startNewSession, loadChatHistory } from '../utils/api';

const SessionDropdown = () => {
  const { state, dispatch } = useCronicle();
  const [isOpen, setIsOpen] = useState(false);
  const [isLoading, setIsLoading] = useState(false);

  // Load session list on component mount
  useEffect(() => {
    loadSessions();
  }, []);

  const loadSessions = async () => {
    try {
      const response = await loadSessionList();
      if (response.success && response.data.sessions) {
        dispatch({ type: ACTIONS.SET_SESSIONS, payload: response.data.sessions });
      }
    } catch (error) {
      console.error('Error loading sessions:', error);
    }
  };

  const handleSessionSwitch = async (sessionId) => {
    if (sessionId === state.currentSessionId) {
      setIsOpen(false);
      return;
    }

    setIsLoading(true);
    setIsOpen(false);

    try {
      // Switch to the selected session
      const switchResponse = await switchSession(sessionId);
      if (switchResponse.success) {
        // Load the chat history for the new session
        const historyResponse = await loadChatHistory();
        if (historyResponse.success) {
          dispatch({ type: ACTIONS.SET_SESSION_ID, payload: historyResponse.data.session_id });
          dispatch({ type: ACTIONS.LOAD_MESSAGES, payload: historyResponse.data.messages });
        }
        // Refresh session list
        loadSessions();
      }
    } catch (error) {
      console.error('Error switching session:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleNewSession = async () => {
    setIsLoading(true);
    setIsOpen(false);

    try {
      const response = await startNewSession();
      if (response.success) {
        dispatch({ type: ACTIONS.CLEAR_MESSAGES });
        dispatch({ type: ACTIONS.SET_SESSION_ID, payload: response.data.session_id });
        dispatch({ type: ACTIONS.SET_SHOW_WELCOME, payload: true });
        // Refresh session list
        loadSessions();
      }
    } catch (error) {
      console.error('Error starting new session:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const getCurrentSessionTitle = () => {
    const currentSession = state.sessions.find(session => session.session_id === state.currentSessionId);
    if (currentSession) {
      return currentSession.title || __('Current Chat', 'cronicle');
    }
    return __('Current Chat', 'cronicle');
  };

  const formatSessionDate = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    
    if (diffDays === 0) {
      return __('Today', 'cronicle');
    } else if (diffDays === 1) {
      return __('Yesterday', 'cronicle');
    } else if (diffDays < 7) {
      return `${diffDays} ${__('days ago', 'cronicle')}`;
    } else {
      return date.toLocaleDateString();
    }
  };

  if (isLoading) {
    return (
      <div className="cronicle-session-dropdown loading">
        <span>{__('Loading...', 'cronicle')}</span>
      </div>
    );
  }

  return (
    <div className="cronicle-session-dropdown">
      <button
        className="cronicle-session-dropdown-trigger"
        onClick={() => setIsOpen(!isOpen)}
        aria-expanded={isOpen}
        aria-haspopup="true"
      >
        <span>{getCurrentSessionTitle()}</span>
        <span className="cronicle-dropdown-arrow">▼</span>
      </button>
      
      {isOpen && (
        <div className="cronicle-session-dropdown-menu">
          <div className="cronicle-session-dropdown-header">
            <button
              className="cronicle-new-session-btn"
              onClick={handleNewSession}
            >
              <span className="dashicons dashicons-plus-alt"></span>
              {__('New Chat', 'cronicle')}
            </button>
          </div>
          
          {state.sessions.length > 0 && (
            <div className="cronicle-session-list">
              <div className="cronicle-session-list-header">
                {__('Previous Chats', 'cronicle')}
              </div>
              {state.sessions.map((session) => (
                <button
                  key={session.session_id}
                  className={`cronicle-session-item ${session.session_id === state.currentSessionId ? 'active' : ''}`}
                  onClick={() => handleSessionSwitch(session.session_id)}
                >
                  <div className="cronicle-session-title">
                    {session.title || __('Untitled Chat', 'cronicle')}
                  </div>
                  <div className="cronicle-session-meta">
                    <span className="cronicle-session-date">
                      {formatSessionDate(session.last_activity)}
                    </span>
                    <span className="cronicle-session-count">
                      {session.message_count} {__('messages', 'cronicle')}
                    </span>
                  </div>
                </button>
              ))}
            </div>
          )}
          
          {state.sessions.length === 0 && (
            <div className="cronicle-no-sessions">
              {__('No previous chats', 'cronicle')}
            </div>
          )}
        </div>
      )}
      
      {isOpen && (
        <div 
          className="cronicle-dropdown-overlay"
          onClick={() => setIsOpen(false)}
        />
      )}
    </div>
  );
};

export default SessionDropdown;