<?php
/**
 * Provide a License area view for the plugin
 *
 * This file is used to mark up the admin-facing aspects of the plugin.
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 */

defined( 'ABSPATH' ) or die();
$code         = filter_input( INPUT_GET, 'code', FILTER_UNSAFE_RAW );
$errMsg       = '';
$aktivShow    = 'd-none';
$registerShow = '';
$file         = '';
if ( $code ) {
	global $license_wp_remote;
    $response = $license_wp_remote->Activate_By_Authorization_Code($code);
	if ($response->status) {
		if ($response->if_file) {
			$file = $this->plugin_dir . $this->config->aktivierungs_file_path . DIRECTORY_SEPARATOR . $this->config->aktivierungs_file;
			file_put_contents($file, $response->install_datei);
		}
		update_option("{$this->basename}_install_time", current_time('mysql'));
		update_option("{$this->basename}_product_install_authorize", true);
		delete_option("{$this->basename}_message");
	} else {
		$errMsg = sprintf(__('%s "%s" could not be activated!','licenseLanguage'),ucfirst($this->config->type), $this->config->name);
	}
	$aktivShow = '';
	$registerShow = 'd-none';
}
$reloadUrl = admin_url();

?>
<div id="wp-activate-license-wrapper">
    <div class="container">
        <div class="card card-license">
            <div class="card-body shadow-license-box">
                <h5 class="card-title"><i class="wp-blue bi bi-exclude"></i>&nbsp;
					<?= $this->config->name ?><small class="text-muted fw-normal" style="font-size: .85rem">
                        ( v<?= $this->version ?> )</small> <?=__('activate', 'licenseLanguage')?>
                </h5>
				<?php if ( get_option( $this->basename . '_message' ) ): ?>
                    <p style="padding: 0 1rem; color: red;text-align: center"><i
                                class="bi bi-exclamation-triangle-fill"></i>&nbsp;
                        <b><?= get_option( $this->basename . '_message' ) ?></b></p>
				<?php endif; ?>
                <hr>
                <div id="licence_display_data">
                    <div class="final-activate <?= $aktivShow ?>">
                        <h5><i class="bi bi-info-circle"></i>&nbsp;<?=__('successfully activated', 'licenseLanguage')?></h5>
                        <a href="<?= $reloadUrl ?>" class="btn btn-primary">
                            <i class="bi bi-exclude"></i>&nbsp;<?=__('Complete activation', 'licenseLanguage')?></a>
                        <hr>
                    </div>
                    <div class="<?= $registerShow ?>">
                        <div class="card-title">
                            <i class="bi bi-share-fill"></i>&nbsp; <?=__('Enter access data', 'licenseLanguage')?>
                        </div>
                        <hr>
                        <div class="form-container">
                            <form id="sendAjaxLicenseForm" action="#" method="post">
                                <input type="hidden" name="method" value="save_license_data">
                                <div class="form-input-wrapper">
                                    <div class="col">
                                        <label for="ClientIDInput" class="form-label">
											<?= __( 'Client ID', 'licenseLanguage' ) ?> <span
                                                    class="text-danger">*</span></label>
                                        <input type="text" name="client_id" class="form-control"
                                               value="<?= get_option( $this->basename . '_client_id' ) ?>"
                                               id="ClientIDInput" autocomplete="cc-number" required>
                                    </div>
                                    <div class="col">
                                        <label for="clientSecretInput" class="form-label">
											<?= __( 'Client secret', 'licenseLanguage') ?> <span
                                                    class="text-danger">*</span></label>
                                        <input type="text" name="client_secret" class="form-control"
                                               value="<?= get_option( $this->basename . '_client_secret' ) ?>"
                                               id="clientSecretInput" autocomplete="cc-number" required>
                                    </div>
                                </div>
                                <?php if(!get_option("{$this->basename}_product_install_authorize")): ?>
                                <button id="saveBtn" type="submit" class="btn btn-primary"><i class="bi bi-save"></i>&nbsp;
	                                <?=__('Save', 'licenseLanguage')?>
                                </button>
                                <?php endif; ?>
                                <span id="activateBtn"></span>
                            </form>
                        </div>
                        <div id="licenseAlert" class="alert alert-danger <?= $errMsg ?: 'd-none' ?>" role="alert">
                            <i class="fa fa-exclamation-triangle"></i>&nbsp; <b><?=__('ERROR', 'licenseLanguage')?>!</b> <span
                                    id="licenseErrMsg"><?= $errMsg ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>