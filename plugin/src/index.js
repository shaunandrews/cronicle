/**
 * Cronicle React App Entry Point
 */

import { render } from '@wordpress/element';
import CronicleApp from './components/CronicleApp';

// Initialize React app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('cronicle-react-root');
  
  if (container) {
    render(<CronicleApp />, container);
  }
});