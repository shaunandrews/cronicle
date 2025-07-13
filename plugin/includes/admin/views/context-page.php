<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if context system is available
$context_manager = function_exists('cronicle_context_manager') ? cronicle_context_manager() : null;
$template_library = function_exists('cronicle_prompt_template_library') ? cronicle_prompt_template_library() : null;
$preferences_engine = function_exists('cronicle_preferences_engine') ? cronicle_preferences_engine() : null;

?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div id="cronicle-context-dashboard">
        <?php if ($context_manager && $template_library && $preferences_engine): ?>
            
            <!-- Context System Status -->
            <div class="card">
                <h2><?php _e('Context System Status', 'cronicle'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Context Manager', 'cronicle'); ?></th>
                        <td><span class="status-indicator success">✓</span> <?php _e('Active', 'cronicle'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Template Library', 'cronicle'); ?></th>
                        <td><span class="status-indicator success">✓</span> <?php _e('Active', 'cronicle'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Preferences Engine', 'cronicle'); ?></th>
                        <td><span class="status-indicator success">✓</span> <?php _e('Active', 'cronicle'); ?></td>
                    </tr>
                </table>
            </div>

            <!-- Context Providers -->
            <div class="card">
                <h2><?php _e('Available Context Providers', 'cronicle'); ?></h2>
                <?php
                $providers = $context_manager->get_providers(false); // Get all providers, not just enabled
                if (!empty($providers)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col"><?php _e('Provider', 'cronicle'); ?></th>
                                <th scope="col"><?php _e('Priority', 'cronicle'); ?></th>
                                <th scope="col"><?php _e('Status', 'cronicle'); ?></th>
                                <th scope="col"><?php _e('Type', 'cronicle'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($providers as $key => $provider_data): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($key); ?></strong></td>
                                    <td><?php echo esc_html($provider_data['priority'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="status-indicator <?php echo $provider_data['enabled'] ? 'success' : 'disabled'; ?>">
                                            <?php echo $provider_data['enabled'] ? '✓ Enabled' : '✗ Disabled'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html(get_class($provider_data['provider'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><?php _e('No context providers available.', 'cronicle'); ?></p>
                <?php endif; ?>
            </div>

            <!-- Prompt Templates -->
            <div class="card">
                <h2><?php _e('Available Prompt Templates', 'cronicle'); ?></h2>
                <?php
                $templates = $template_library->get_templates(); // Correct method name
                if (!empty($templates)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col"><?php _e('Template Key', 'cronicle'); ?></th>
                                <th scope="col"><?php _e('Category', 'cronicle'); ?></th>
                                <th scope="col"><?php _e('Name', 'cronicle'); ?></th>
                                <th scope="col"><?php _e('Description', 'cronicle'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($templates as $key => $template): ?>
                                <tr>
                                    <td><code><?php echo esc_html($key); ?></code></td>
                                    <td><?php echo esc_html($template['category'] ?? 'general'); ?></td>
                                    <td><?php echo esc_html($template['name'] ?? $key); ?></td>
                                    <td><?php echo esc_html($template['description'] ?? __('No description available', 'cronicle')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><?php _e('No prompt templates available.', 'cronicle'); ?></p>
                <?php endif; ?>
            </div>

            <!-- User Preferences -->
            <div class="card">
                <h2><?php _e('Current User Preferences', 'cronicle'); ?></h2>
                <?php
                $user_preferences = $preferences_engine->get_user_preferences();
                if (!empty($user_preferences)): ?>
                    <div class="context-preview">
                        <h3><?php _e('Writing Style', 'cronicle'); ?></h3>
                        <?php if (isset($user_preferences['writing_style'])): ?>
                            <ul>
                                <?php foreach ($user_preferences['writing_style'] as $key => $value): ?>
                                    <li><strong><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?>:</strong> <?php echo esc_html(is_bool($value) ? ($value ? 'Yes' : 'No') : $value); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        
                        <h3><?php _e('Context Providers', 'cronicle'); ?></h3>
                        <?php if (isset($user_preferences['context_providers'])): ?>
                            <ul>
                                <?php foreach ($user_preferences['context_providers'] as $key => $enabled): ?>
                                    <li><strong><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?>:</strong> 
                                        <span class="status-indicator <?php echo $enabled ? 'success' : 'disabled'; ?>">
                                            <?php echo $enabled ? '✓ Enabled' : '✗ Disabled'; ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p><?php _e('No user preferences set. Using defaults.', 'cronicle'); ?></p>
                <?php endif; ?>
            </div>

            <!-- Site Preferences -->
            <div class="card">
                <h2><?php _e('Site-wide Preferences', 'cronicle'); ?></h2>
                <?php
                $site_preferences = $preferences_engine->get_site_preferences();
                if (!empty($site_preferences)): ?>
                    <div class="context-preview">
                        <?php if (isset($site_preferences['ai_settings'])): ?>
                            <h3><?php _e('AI Settings', 'cronicle'); ?></h3>
                            <ul>
                                <?php foreach ($site_preferences['ai_settings'] as $key => $value): ?>
                                    <li><strong><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?>:</strong> <?php echo esc_html($value); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        
                        <?php if (isset($site_preferences['default_context_providers'])): ?>
                            <h3><?php _e('Default Context Providers', 'cronicle'); ?></h3>
                            <ul>
                                <?php foreach ($site_preferences['default_context_providers'] as $key => $enabled): ?>
                                    <li><strong><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?>:</strong> 
                                        <span class="status-indicator <?php echo $enabled ? 'success' : 'disabled'; ?>">
                                            <?php echo $enabled ? '✓ Enabled' : '✗ Disabled'; ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p><?php _e('No site preferences configured. Using defaults.', 'cronicle'); ?></p>
                <?php endif; ?>
            </div>

            <!-- Detailed Context Data Preview -->
            <div class="card">
                <h2><?php _e('Current Context Data Preview', 'cronicle'); ?></h2>
                <p><?php _e('See the actual context data that would be sent with AI requests.', 'cronicle'); ?></p>
                
                <?php
                // Generate sample context data
                try {
                    $sample_options = array(
                        'topic' => 'sample topic',
                        'mode' => 'draft'
                    );
                    $current_context = $context_manager->gather_context($sample_options, false); // Don't use cache for fresh data
                    
                    if (!empty($current_context)): ?>
                        <div class="context-data-tabs">
                            <nav class="nav-tab-wrapper">
                                <?php 
                                $first_tab = true;
                                foreach ($current_context as $provider_key => $provider_data): ?>
                                    <a href="#context-<?php echo esc_attr($provider_key); ?>" 
                                       class="nav-tab <?php echo $first_tab ? 'nav-tab-active' : ''; ?>"
                                       onclick="showContextTab('<?php echo esc_js($provider_key); ?>')">
                                        <?php echo esc_html(ucwords(str_replace('_', ' ', $provider_key))); ?>
                                    </a>
                                    <?php $first_tab = false; ?>
                                <?php endforeach; ?>
                                <a href="#context-formatted" 
                                   class="nav-tab"
                                   onclick="showContextTab('formatted')">
                                    <?php _e('Formatted Output', 'cronicle'); ?>
                                </a>
                            </nav>
                            
                            <div class="tab-content">
                                <?php 
                                $first_content = true;
                                foreach ($current_context as $provider_key => $provider_data): ?>
                                    <div id="context-<?php echo esc_attr($provider_key); ?>" 
                                         class="context-tab-panel <?php echo $first_content ? 'active' : ''; ?>">
                                        <h3><?php echo esc_html(ucwords(str_replace('_', ' ', $provider_key))); ?> Context</h3>
                                        <div class="context-data-display">
                                            <?php if (is_array($provider_data)): ?>
                                                <?php echo cronicle_render_context_data_table($provider_data); ?>
                                            <?php else: ?>
                                                <pre><?php echo esc_html(print_r($provider_data, true)); ?></pre>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php $first_content = false; ?>
                                <?php endforeach; ?>
                                
                                <div id="context-formatted" class="context-tab-panel">
                                    <h3><?php _e('Formatted Context String', 'cronicle'); ?></h3>
                                    <p><?php _e('This is how the context appears in the final prompt sent to AI:', 'cronicle'); ?></p>
                                    <div class="context-formatted-output">
                                        <textarea readonly rows="20" style="width: 100%; font-family: monospace; font-size: 12px;"><?php 
                                            echo esc_textarea($context_manager->build_context_string($current_context, 'structured')); 
                                        ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="notice notice-warning inline">
                            <p><?php _e('No context data available. This may indicate that context providers are not properly configured.', 'cronicle'); ?></p>
                        </div>
                    <?php endif;
                } catch (Exception $e) {
                    echo '<div class="notice notice-error inline"><p>' . sprintf(__('Error generating context preview: %s', 'cronicle'), esc_html($e->getMessage())) . '</p></div>';
                }
                ?>
            </div>

            <!-- Context Generation Test -->
            <div class="card">
                <h2><?php _e('Context Generation Test', 'cronicle'); ?></h2>
                <p><?php _e('Test how context changes for different topics and modes.', 'cronicle'); ?></p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('cronicle_test_context', 'cronicle_context_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="test_topic"><?php _e('Test Topic', 'cronicle'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="test_topic" name="test_topic" class="regular-text" 
                                       value="<?php echo esc_attr($_POST['test_topic'] ?? 'benefits of exercise'); ?>" 
                                       placeholder="<?php _e('Enter a topic to test context generation', 'cronicle'); ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="test_mode"><?php _e('Content Mode', 'cronicle'); ?></label>
                            </th>
                            <td>
                                <select id="test_mode" name="test_mode">
                                    <option value="draft" <?php selected($_POST['test_mode'] ?? 'draft', 'draft'); ?>><?php _e('Draft', 'cronicle'); ?></option>
                                    <option value="outline" <?php selected($_POST['test_mode'] ?? 'draft', 'outline'); ?>><?php _e('Outline', 'cronicle'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('Generate Test Context', 'cronicle'), 'secondary'); ?>
                </form>

                <?php
                // Handle context test
                if (isset($_POST['test_topic']) && wp_verify_nonce($_POST['cronicle_context_nonce'], 'cronicle_test_context')) {
                    $test_topic = sanitize_text_field($_POST['test_topic']);
                    $test_mode = sanitize_text_field($_POST['test_mode']);
                    
                    if (!empty($test_topic)) {
                        try {
                            // Prepare context options
                            $context_options = array(
                                'topic' => $test_topic,
                                'mode' => $test_mode
                            );
                            
                            // Get template
                            $template_key = $test_mode === 'outline' ? 'content-outline' : 'blog-post-professional';
                            $template_variables = array(
                                'topic' => $test_topic,
                                'mode' => $test_mode,
                                'is_outline' => $test_mode === 'outline'
                            );
                            
                            // Generate context
                            $generated_context = $context_manager->gather_context($context_options);
                            
                            echo '<div class="context-test-result">';
                            echo '<h3>' . __('Generated Context', 'cronicle') . '</h3>';
                            echo '<textarea readonly rows="15" style="width: 100%; font-family: monospace;">';
                            echo esc_textarea(print_r($generated_context, true));
                            echo '</textarea>';
                            echo '</div>';
                            
                        } catch (Exception $e) {
                            echo '<div class="notice notice-error"><p>' . sprintf(__('Error generating context: %s', 'cronicle'), esc_html($e->getMessage())) . '</p></div>';
                        }
                    }
                }
                ?>
            </div>

        <?php else: ?>
            <!-- Context System Not Available -->
            <div class="notice notice-warning">
                <p><?php _e('The Cronicle context system is not fully available. Some components may be missing:', 'cronicle'); ?></p>
                <ul>
                    <?php if (!$context_manager): ?>
                        <li><?php _e('Context Manager - Not loaded', 'cronicle'); ?></li>
                    <?php endif; ?>
                    <?php if (!$template_library): ?>
                        <li><?php _e('Template Library - Not loaded', 'cronicle'); ?></li>
                    <?php endif; ?>
                    <?php if (!$preferences_engine): ?>
                        <li><?php _e('Preferences Engine - Not loaded', 'cronicle'); ?></li>
                    <?php endif; ?>
                </ul>
                <p><?php _e('Please check that all context system files are properly installed.', 'cronicle'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
/**
 * Helper function to render context data as a readable table
 */
function cronicle_render_context_data_table($data, $level = 0) {
    $output = '';
    $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
    
    if (is_array($data)) {
        $output .= '<table class="context-data-table">';
        foreach ($data as $key => $value) {
            $output .= '<tr>';
            $output .= '<td class="context-key">' . $indent . '<strong>' . esc_html($key) . ':</strong></td>';
            $output .= '<td class="context-value">';
            
            if (is_array($value)) {
                if (empty($value)) {
                    $output .= '<em>empty array</em>';
                } else {
                    $output .= cronicle_render_context_data_table($value, $level + 1);
                }
            } elseif (is_bool($value)) {
                $output .= '<span class="context-boolean ' . ($value ? 'true' : 'false') . '">' . ($value ? 'true' : 'false') . '</span>';
            } elseif (is_null($value)) {
                $output .= '<em>null</em>';
            } elseif (is_string($value) && strlen($value) > 100) {
                $output .= '<details><summary>' . esc_html(substr($value, 0, 100)) . '...</summary>';
                $output .= '<pre class="long-text">' . esc_html($value) . '</pre></details>';
            } else {
                $output .= '<span class="context-string">' . esc_html($value) . '</span>';
            }
            
            $output .= '</td>';
            $output .= '</tr>';
        }
        $output .= '</table>';
    } else {
        $output .= '<span class="context-simple">' . esc_html($data) . '</span>';
    }
    
    return $output;
}
?>

<style>
#cronicle-context-dashboard .card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-bottom: 20px;
    padding: 20px;
}

#cronicle-context-dashboard .card h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.status-indicator {
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
}

.status-indicator.success {
    background: #d4edda;
    color: #155724;
}

.status-indicator.disabled {
    background: #f8d7da;
    color: #721c24;
}

.context-preview ul {
    list-style: none;
    padding-left: 0;
}

.context-preview li {
    padding: 5px 0;
    border-bottom: 1px solid #f0f0f0;
}

.context-preview li:last-child {
    border-bottom: none;
}

.context-test-result {
    margin-top: 20px;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

/* Context Data Tabs */
.context-data-tabs {
    margin-top: 15px;
}

.context-tab-panel {
    display: none;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-top: none;
    background: #fff;
}

.context-tab-panel.active {
    display: block;
}

/* Context Data Table */
.context-data-table {
    width: 100%;
    border-collapse: collapse;
    margin: 10px 0;
    font-size: 13px;
}

.context-data-table td {
    padding: 8px 12px;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: top;
}

.context-key {
    width: 30%;
    background: #f8f9fa;
    font-weight: bold;
}

.context-value {
    width: 70%;
}

.context-boolean.true {
    color: #28a745;
    font-weight: bold;
}

.context-boolean.false {
    color: #dc3545;
    font-weight: bold;
}

.context-string {
    color: #333;
}

.long-text {
    max-height: 200px;
    overflow-y: auto;
    background: #f8f9fa;
    padding: 10px;
    border-radius: 3px;
    font-size: 12px;
    margin: 5px 0;
}

.context-formatted-output textarea {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 12px;
}

/* Nested tables */
.context-data-table .context-data-table {
    margin: 5px 0;
    border: 1px solid #e0e0e0;
}

.context-data-table .context-data-table td {
    padding: 5px 8px;
    font-size: 12px;
}

/* Context data display improvements */
.context-data-display {
    max-height: 500px;
    overflow-y: auto;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    background: #fff;
}

details summary {
    cursor: pointer;
    padding: 5px;
    background: #f0f0f0;
    border-radius: 3px;
}

details[open] summary {
    margin-bottom: 10px;
}
</style>

<script>
function showContextTab(tabId) {
    // Hide all tab panels
    const panels = document.querySelectorAll('.context-tab-panel');
    panels.forEach(panel => panel.classList.remove('active'));
    
    // Remove active class from all tabs
    const tabs = document.querySelectorAll('.nav-tab');
    tabs.forEach(tab => tab.classList.remove('nav-tab-active'));
    
    // Show selected panel
    const selectedPanel = document.getElementById('context-' + tabId);
    if (selectedPanel) {
        selectedPanel.classList.add('active');
    }
    
    // Add active class to clicked tab
    const clickedTab = event.target;
    clickedTab.classList.add('nav-tab-active');
    
    // Prevent default link behavior
    if (event) {
        event.preventDefault();
    }
}
</script>