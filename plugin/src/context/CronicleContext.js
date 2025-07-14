/**
 * React Context for Cronicle App State Management
 */

import { createContext, useContext, useReducer } from '@wordpress/element';

// Initial state
const initialState = {
  messages: [],
  currentDraft: null,
  currentSessionId: null,
  sessions: [],
  selectedMode: 'draft',
  selectedTemplate: 'auto', // Auto-select best template
  enabledContextProviders: {
    site: true,
    user: true,
    writing_style: true,
    content: false,
    conversation: false
  },
  isTyping: false,
  isSending: false,
  showPreview: false,
  showWelcome: true,
};

// Action types
export const ACTIONS = {
  ADD_MESSAGE: 'ADD_MESSAGE',
  SET_CURRENT_DRAFT: 'SET_CURRENT_DRAFT',
  SET_SESSION_ID: 'SET_SESSION_ID',
  SET_SESSIONS: 'SET_SESSIONS',
  SET_MODE: 'SET_MODE',
  SET_TEMPLATE: 'SET_TEMPLATE',
  SET_CONTEXT_PROVIDER: 'SET_CONTEXT_PROVIDER',
  SET_TYPING: 'SET_TYPING',
  SET_SENDING: 'SET_SENDING',
  SET_SHOW_PREVIEW: 'SET_SHOW_PREVIEW',
  SET_SHOW_WELCOME: 'SET_SHOW_WELCOME',
  CLEAR_MESSAGES: 'CLEAR_MESSAGES',
  LOAD_MESSAGES: 'LOAD_MESSAGES',
};

// Reducer function
const cronicleReducer = (state, action) => {
  switch (action.type) {
    case ACTIONS.ADD_MESSAGE:
      return {
        ...state,
        messages: [...state.messages, action.payload],
        showWelcome: false,
      };
    
    case ACTIONS.SET_CURRENT_DRAFT:
      return {
        ...state,
        currentDraft: action.payload,
      };
    
    case ACTIONS.SET_SESSION_ID:
      return {
        ...state,
        currentSessionId: action.payload,
      };
    
    case ACTIONS.SET_SESSIONS:
      return {
        ...state,
        sessions: action.payload,
      };
    
    case ACTIONS.SET_MODE:
      return {
        ...state,
        selectedMode: action.payload,
      };
    
    case ACTIONS.SET_TEMPLATE:
      return {
        ...state,
        selectedTemplate: action.payload,
      };
    
    case ACTIONS.SET_CONTEXT_PROVIDER:
      return {
        ...state,
        enabledContextProviders: {
          ...state.enabledContextProviders,
          [action.payload.provider]: action.payload.enabled,
        },
      };
    
    case ACTIONS.SET_TYPING:
      return {
        ...state,
        isTyping: action.payload,
      };
    
    case ACTIONS.SET_SENDING:
      return {
        ...state,
        isSending: action.payload,
      };
    
    case ACTIONS.SET_SHOW_PREVIEW:
      return {
        ...state,
        showPreview: action.payload,
      };
    
    case ACTIONS.SET_SHOW_WELCOME:
      return {
        ...state,
        showWelcome: action.payload,
      };
    
    case ACTIONS.CLEAR_MESSAGES:
      return {
        ...state,
        messages: [],
        currentDraft: null,
        showWelcome: true,
        showPreview: false,
      };
    
    case ACTIONS.LOAD_MESSAGES:
      return {
        ...state,
        messages: action.payload,
        showWelcome: action.payload.length === 0,
      };
    
    default:
      return state;
  }
};

// Create context
const CronicleContext = createContext();

// Provider component
export const CronicleProvider = ({ children }) => {
  const [state, dispatch] = useReducer(cronicleReducer, initialState);

  return (
    <CronicleContext.Provider value={{ state, dispatch }}>
      {children}
    </CronicleContext.Provider>
  );
};

// Custom hook to use the context
export const useCronicle = () => {
  const context = useContext(CronicleContext);
  if (!context) {
    throw new Error('useCronicle must be used within a CronicleProvider');
  }
  return context;
};