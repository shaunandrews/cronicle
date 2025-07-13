/**
 * Main Cronicle React Application Component
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import CronicleHeader from './CronicleHeader';
import ChatContainer from './ChatContainer';
import PreviewPanel from './PreviewPanel';
import { CronicleProvider } from '../context/CronicleContext';

const CronicleApp = () => {
  const [isApiConfigured, setIsApiConfigured] = useState(false);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    // Check if API is configured from localized data
    const checkApiConfig = () => {
      if (window.cronicle_ajax) {
        setIsApiConfigured(!!window.cronicle_ajax.api_configured);
        setIsLoading(false);
      } else {
        // If cronicle_ajax isn't available yet, wait a bit
        setTimeout(checkApiConfig, 100);
      }
    };
    
    checkApiConfig();
  }, []);

  if (isLoading) {
    return <div>Loading...</div>;
  }

  if (!isApiConfigured) {
    return (
      <div className="wrap">
        <div className="cronicle-container">
          <CronicleHeader isApiConfigured={false} />
          <div className="cronicle-setup-notice">
            <p>
              <strong>{__('Welcome to Cronicle!', 'cronicle')}</strong><br />
              {__('To get started, you need to configure your Anthropic API key.', 'cronicle')}
            </p>
            <p>
              <a 
                href={`${window.location.origin}/wp-admin/options-general.php?page=cronicle-settings`} 
                className="button button-primary"
              >
                {__('Configure API Key', 'cronicle')}
              </a>
            </p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <CronicleProvider>
      <div className="wrap">
        <div className="cronicle-container">
          <CronicleHeader isApiConfigured={true} />
          <div className="cronicle-main-content">
            <ChatContainer />
            <PreviewPanel />
          </div>
        </div>
      </div>
    </CronicleProvider>
  );
};

export default CronicleApp;