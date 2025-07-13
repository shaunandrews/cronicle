<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Variables should be passed from the UI class
$is_api_configured = isset($is_api_configured) ? $is_api_configured : false;
?>

<div class="wrap">
    <div class="cronicle-container">
        <div class="cronicle-header">
            <h1><?php _e('Cronicle AI Assistant', 'cronicle'); ?></h1>
            <div class="cronicle-header-right">
                <?php if ($is_api_configured): ?>
                <div class="cronicle-session-controls">
                    <button type="button" class="button cronicle-new-session-btn">
                        <?php _e('New Chat', 'cronicle'); ?>
                    </button>
                </div>
                <div class="cronicle-mode-selector">
                    <label for="cronicle-mode-select"><?php _e('Mode:', 'cronicle'); ?></label>
                    <select id="cronicle-mode-select" class="cronicle-mode-select">
                        <option value="draft"><?php _e('Full Draft', 'cronicle'); ?></option>
                        <option value="outline"><?php _e('Outline', 'cronicle'); ?></option>
                    </select>
                </div>
                <?php endif; ?>
                <span class="cronicle-status <?php echo $is_api_configured ? 'connected' : 'disconnected'; ?>">
                    <?php echo $is_api_configured ? __('Connected', 'cronicle') : __('Not Connected', 'cronicle'); ?>
                </span>
            </div>
        </div>
        
        <?php if (!$is_api_configured): ?>
            <div class="cronicle-setup-notice">
                <p>
                    <strong><?php _e('Welcome to Cronicle!', 'cronicle'); ?></strong><br>
                    <?php _e('To get started, you need to configure your Anthropic API key.', 'cronicle'); ?>
                </p>
                <p>
                    <a href="<?php echo admin_url('options-general.php?page=cronicle-settings'); ?>" class="button button-primary">
                        <?php _e('Configure API Key', 'cronicle'); ?>
                    </a>
                </p>
            </div>
        <?php else: ?>
            <div class="cronicle-main-content">
                <div class="cronicle-chat-container">
                    <div class="cronicle-welcome-message">
                        <?php _e('Create a new draft with AI', 'cronicle'); ?>
                    </div>
                    <div class="cronicle-messages"></div>
                    
                    <div class="cronicle-typing-indicator">
                        <?php _e('Assistant is typing...', 'cronicle'); ?>
                    </div>
                    
                    <div class="cronicle-input-area">
                        <form class="cronicle-input-form">
                            <textarea 
                                class="cronicle-input" 
                                placeholder="<?php esc_attr_e('What would you like to write about? (e.g., benefits of exercise, cooking tips, travel destinations)', 'cronicle'); ?>"
                                rows="1"
                            ></textarea>
                            <button type="submit" class="cronicle-send-button">
                                <?php _e('Send', 'cronicle'); ?>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="cronicle-preview-container">
                    <div class="cronicle-preview-header">
                        <?php _e('Post Preview', 'cronicle'); ?>
                    </div>
                    <div class="cronicle-preview-content">
                        <!-- Preview content will be populated by JavaScript -->
                    </div>
                    <div class="cronicle-preview-actions">
                        <!-- Preview actions will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div> 