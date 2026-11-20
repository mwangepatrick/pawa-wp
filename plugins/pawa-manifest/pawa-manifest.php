<?php
/**
 * Plugin Name: Pawa Manifest Viewer
 * Description: Displays manifest data (from postmeta `manifest_data`) in a fancy frontend layout using shortcode [pawa_manifest].
 * Version: 1.0.1
 * Author: Cosmo Ninja
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Register shortcode
add_shortcode('pawa_manifest', function($atts){
    global $post;

    // Get manifest_data from postmeta
    $manifest_data = get_post_meta($post->ID, 'manifest_data', true);

    if(!$manifest_data) {
        return "<p><strong>No manifest data available.</strong></p>";
    }

    // Decode JSON
    $data = json_decode($manifest_data, true);
    if(!$data) {
        return "<p><strong>Invalid manifest data format.</strong></p>";
    }

    ob_start();
    ?>
    <style>
        .pawa-container {
            font-family: Arial, sans-serif;
            margin: 20px auto;
            max-width: 1100px;
        }
        .pawa-section {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform .2s ease-in-out;
        }
        .pawa-section:hover {
            transform: translateY(-3px);
        }
        .pawa-header {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .pawa-header span {
            background: #0073aa;
            color: #fff;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 14px;
        }
        .pawa-table {
            width: 100%;
            border-collapse: collapse;
        }
        .pawa-table th, .pawa-table td {
            border: 1px solid #ddd;
            padding: 10px;
            font-size: 14px;
        }
        .pawa-table th {
            background: #f5f5f5;
            font-weight: 600;
            text-align: left;
        }
        .pawa-link {
            display: inline-block;
            padding: 6px 12px;
            background: #0073aa;
            color: #fff !important;
            border-radius: 6px;
            text-decoration: none;
            transition: background .2s;
        }
        .pawa-link:hover {
            background: #005177;
        }
        /* Latest release: responsive card/grid layout */
    .latest-block {
            border: 0;
            padding: 10px 0 22px 0;
            margin-bottom: 30px;
            background: linear-gradient(180deg, rgba(240,248,255,0.9), rgba(255,255,255,0.6));
            border-radius: 14px;
        }
        /* permalink anchor styling */
        .section-anchor {
            color: #0073aa;
            text-decoration: none;
            font-size: 13px;
            opacity: 0.9;
            margin-left: 8px;
        }
        .section-anchor:hover { opacity: 1; }
        .latest-block h3 {
            margin: 0 0 12px 0;
            color: #0073aa;
            display: flex;
            align-items: baseline;
            gap: 12px;
            flex-wrap: wrap;
        }
        .latest-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 14px;
            align-items: start;
        }
        .latest-card {
            background: #fff;
            border-radius: 10px;
            padding: 12px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.06);
            border: 1px solid rgba(0,0,0,0.04);
            display: flex;
            flex-direction: column;
            min-height: 110px;
            word-break: break-word;
        }
        .card-header { display:flex; justify-content:space-between; gap:8px; align-items:center; }
        .proj-badge { background:#e8f6ff; color:#0073aa; padding:4px 8px; border-radius:999px; font-size:12px; }
        .file-name { margin:8px 0; font-weight:600; font-size:14px; color:#222; }
        .meta { display:flex; gap:10px; font-size:12px; color:#666; flex-wrap:wrap; }
        .download-row { margin-top:auto; display:flex; justify-content:space-between; align-items:center; gap:8px; }
        .file-hash { font-family: monospace; background:#f7f7f7; padding:3px 6px; border-radius:6px; }

        @media (max-width: 520px) {
            .pawa-container { padding: 12px; }
            .latest-block { padding: 10px; }
            .file-name { font-size:13px; }
            .latest-grid { gap:10px; }
            .pawa-header { font-size:18px; }
        }
    </style>

    <div class="pawa-container">

        <?php if(isset($data['latest'])): ?>
            <?php $latest_id = 'latest-release'; ?>
            <div id="<?php echo esc_attr($latest_id); ?>" class="latest-block">
                <h3>🚀 Latest Release: v<?php echo esc_html($data['latest']['version']); ?> <small>(<?php echo esc_html($data['latest']['date']); ?>)</small>
                    <a class="section-anchor" href="#<?php echo esc_attr($latest_id); ?>" aria-label="Link to latest release">#</a>
                </h3>
                <div class="latest-grid">
                    <?php
                        // Build a display list where Ushuru appears only as its last occurrence
                        $latest_display = array();
                        $ushuru_last = null;
                        foreach($data['latest']['files'] as $file) {
                            if (isset($file['project']) && strtolower($file['project']) === 'ushuru') {
                                $ushuru_last = $file; // override until we reach the last
                            } else {
                                $latest_display[] = $file;
                            }
                        }
                        if ($ushuru_last !== null) { $latest_display[] = $ushuru_last; }

                        foreach($latest_display as $file): ?>
                        <div class="latest-card">
                            <div class="card-header">
                                <div class="proj-badge"><?php echo esc_html($file['project']); ?></div>
                                <div class="meta"><span><?php echo esc_html($file['version']); ?></span></div>
                            </div>

                            <div class="file-name">
                                <a class="pawa-link" style="background:transparent;color:#0073aa;padding:0;" href="<?php echo esc_url($file['link']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($file['file']); ?></a>
                            </div>

                            <div class="meta">
                                <div><?php echo esc_html($file['date']); ?></div>
                                <div class="file-hash"><?php echo esc_html(substr($file['hash'],0,12)); ?>...</div>
                            </div>

                            <div class="download-row">
                                <a class="pawa-link" href="<?php echo esc_url($file['link']); ?>" target="_blank" rel="noopener noreferrer">Download</a>
                                <div style="font-size:12px;color:#999">&nbsp;</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php foreach($data as $project => $files): ?>
            <?php if($project === "latest") continue; ?>
            <?php $proj_id = sanitize_title($project); ?>
            <?php
                // By default show all files for a project
                $displayFiles = $files;
                // Special-case: for Ushuru, display only the last entry (manifest order)
                if (strtolower($project) === 'ushuru' && is_array($files) && count($files) > 0) {
                    // Use array_key_last when available
                    if (function_exists('array_key_last')) {
                        $lastKey = array_key_last($files);
                        $displayFiles = array($lastKey => $files[$lastKey]);
                    } else {
                        // Fallback for older PHP: iterate and keep the last
                        $lastKey = null;
                        foreach($files as $k => $v) { $lastKey = $k; }
                        if ($lastKey !== null) { $displayFiles = array($lastKey => $files[$lastKey]); }
                    }
                }
            ?>
            <div id="<?php echo esc_attr($proj_id); ?>" class="pawa-section">
                <div class="pawa-header"><?php echo esc_html($project); ?> <span><?php echo count($displayFiles); ?> file(s)</span>
                    <a class="section-anchor" href="#<?php echo esc_attr($proj_id); ?>" aria-label="Link to <?php echo esc_attr($project); ?>">#</a>
                </div>
                <table class="pawa-table">
                    <thead>
                        <tr>
                            <th>File</th>
                            <th>Version</th>
                            <th>Date</th>
                            <th>Hash</th>
                            <th>Download</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($displayFiles as $filename => $details): ?>
                            <tr>
                                <td><?php echo esc_html($details['file']); ?></td>
                                <td><?php echo esc_html($details['version']); ?></td>
                                <td><?php echo esc_html($details['date']); ?></td>
                                <td><code><?php echo esc_html(substr($details['hash'],0,12)); ?>...</code></td>
                                <td><a class="pawa-link" href="<?php echo esc_url($details['link']); ?>" target="_blank">Download</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>

    </div>
    <?php
    return ob_get_clean();
});
