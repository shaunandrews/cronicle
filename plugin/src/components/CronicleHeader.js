/**
 * Cronicle Header Component
 */

import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useCronicle } from '../context/CronicleContext';
import { ACTIONS } from '../context/CronicleContext';
import { startNewSession } from '../utils/api';
import SessionDropdown from './SessionDropdown';

const CronicleHeader = ({ isApiConfigured }) => {
  // Only use context when API is configured
  let state = { selectedMode: 'draft' };
  let dispatch = () => {};
  
  if (isApiConfigured) {
    try {
      const context = useCronicle();
      state = context.state;
      dispatch = context.dispatch;
    } catch (error) {
      // Context not available, use defaults
    }
  }
  


  return (
    <div className="cronicle-header">
      <h1>{__('Cronicle AI Assistant', 'cronicle')}</h1>
      <div className="cronicle-header-right">
        {isApiConfigured && (
          <div className="cronicle-session-controls">
            <SessionDropdown />
          </div>
        )}
        <span className={`cronicle-status ${isApiConfigured ? 'connected' : 'disconnected'}`}>
          {isApiConfigured ? __('Connected', 'cronicle') : __('Not Connected', 'cronicle')}
        </span>
      </div>
    </div>
  );
};

export default CronicleHeader;