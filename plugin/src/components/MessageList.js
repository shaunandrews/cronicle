/**
 * Message List Component
 */

import { useEffect, useRef } from '@wordpress/element';
import Message from './Message';

const MessageList = ({ messages }) => {
  const messagesEndRef = useRef(null);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  return (
    <div className="cronicle-messages">
      {messages.map((message, index) => (
        <Message 
          key={`${message.timestamp || Date.now()}-${index}`} 
          message={message} 
        />
      ))}
      <div ref={messagesEndRef} />
    </div>
  );
};

export default MessageList;