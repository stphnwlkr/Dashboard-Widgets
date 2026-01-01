<?php
/**
 * Dashboard Layout: CSS Grid for WIDGET AREAS (drop zones), not widgets.
 * - Grid container: #dashboard-widgets
 * - Grid items: .postbox-container (each contains one sortable drop zone)
 * - Columns: 1–4 (WP dashboard provides up to 4 containers)
 * - Per-area span UI (in Screen Options) now populates reliably (DOMContentLoaded)
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class FWE_Dashboard_Area_Grid_V2 {
	const META_COLS  = 'fwe_dash_area_grid_cols';
	const META_SPANS = 'fwe_dash_area_grid_spans'; // array keyed by container id => int span

	const MIN_COLS = 1;
	const MAX_COLS = 4; // hard cap per your request

	public static function init() {
		add_action( 'current_screen', [ __CLASS__, 'maybe_add_screen_ui' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );

		add_action( 'wp_ajax_fwe_dash_area_grid_save_cols', [ __CLASS__, 'ajax_save_cols' ] );
		add_action( 'wp_ajax_fwe_dash_area_grid_save_spans', [ __CLASS__, 'ajax_save_spans' ] );
	}

	public static function maybe_add_screen_ui( $screen ) {
		if ( ! $screen || $screen->id !== 'dashboard' ) { return; }
		add_filter( 'screen_settings', [ __CLASS__, 'screen_settings_ui' ], 10, 2 );
	}

	public static function screen_settings_ui( $settings, $screen ) {
		if ( ! $screen || $screen->id !== 'dashboard' ) { return $settings; }

		$cols      = self::get_cols();
		$spans     = self::get_user_spans();
		$nonceCols = wp_create_nonce( 'fwe_dash_area_grid_cols' );
		$nonceSpan = wp_create_nonce( 'fwe_dash_area_grid_spans' );

		$span_ui_enabled = (bool) apply_filters( 'fwe_dash_area_grid_span_ui_enabled', true );

		ob_start();
		?>
		<fieldset class="fwe-dash-area-grid-screenopt" style="margin-top:10px;">
			<legend class="screen-reader-text"><?php esc_html_e( 'Dashboard layout', 'fwe' ); ?></legend>

			<div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
				<label for="fwe_dash_area_grid_cols" style="display:inline-block; min-width: 170px;">
					<?php esc_html_e( 'Widget area columns', 'fwe' ); ?>
				</label>

				<select id="fwe_dash_area_grid_cols"
				        data-nonce="<?php echo esc_attr( $nonceCols ); ?>">
					<?php for ( $i = self::MIN_COLS; $i <= self::MAX_COLS; $i++ ) : ?>
						<option value="<?php echo (int) $i; ?>" <?php selected( $cols, $i ); ?>>
							<?php echo (int) $i; ?>
						</option>
					<?php endfor; ?>
				</select>

				<span class="description" style="margin-left:6px;">
					<?php esc_html_e( 'Saves automatically.', 'fwe' ); ?>
				</span>
			</div>

			<?php if ( $span_ui_enabled ) : ?>
				<hr style="margin:12px 0;">

				<div id="fwe-dash-area-span-wrap"
				     data-nonce="<?php echo esc_attr( $nonceSpan ); ?>"
				     data-saved="<?php echo esc_attr( wp_json_encode( is_array( $spans ) ? $spans : [] ) ); ?>">
					<div style="font-weight:600; margin-bottom:6px;">
						<?php esc_html_e( 'Widget area spans', 'fwe' ); ?>
					</div>

					<div class="description" style="margin-bottom:10px;">
						<?php esc_html_e( 'Set how many columns each widget area spans in the grid.', 'fwe' ); ?>
					</div>

					<div id="fwe-dash-area-span-list" style="display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(300px, 100%), 1fr));
  gap: var(--grid-gap, 12px);"></div>

					<p id="fwe-dash-area-span-empty" class="description" style="display:none; margin-top:8px;">
						<?php esc_html_e( 'No widget areas detected.', 'fwe' ); ?>
					</p>
				</div>
			<?php endif; ?>
		</fieldset>

		<script>
		(function(){
			// Save column count (AJAX)
			const colSel = document.getElementById('fwe_dash_area_grid_cols');
			if(colSel){
				colSel.addEventListener('change', function(){
					const fd = new FormData();
					fd.append('action','fwe_dash_area_grid_save_cols');
					fd.append('nonce', colSel.dataset.nonce || '');
					fd.append('cols', colSel.value);

					fetch(ajaxurl, { method:'POST', body: fd, credentials:'same-origin' })
						.then(() => window.location.reload())
						.catch(() => window.location.reload());
				});
			}

			// Build + save per-area spans (AJAX)
			const spanWrap = document.getElementById('fwe-dash-area-span-wrap');
			const listEl   = document.getElementById('fwe-dash-area-span-list');
			const emptyEl  = document.getElementById('fwe-dash-area-span-empty');
			if(!spanWrap || !listEl) return;

			let saved = {};
			try { saved = JSON.parse(spanWrap.dataset.saved || '{}') || {}; } catch(e){ saved = {}; }

			function mkSpanSelect(current){
				const s = document.createElement('select');
				for(let i=1;i<=<?php echo (int) self::MAX_COLS; ?>;i++){
					const o = document.createElement('option');
					o.value = String(i);
					o.textContent = String(i);
					if(i === current) o.selected = true;
					s.appendChild(o);
				}
				return s;
			}

			function discoverAreas(){
				const dash = document.getElementById('dashboard-widgets');
				if(!dash) return [];
				return Array.from(dash.querySelectorAll('.postbox-container[id]')).map(c => c.id);
			}

			function renderAreaSpans(){
				listEl.innerHTML = '';
				const areaIds = discoverAreas();

				if(!areaIds.length){
					if(emptyEl) emptyEl.style.display = 'block';
					return;
				}
				if(emptyEl) emptyEl.style.display = 'none';

				areaIds.forEach((id, idx) => {
					const row = document.createElement('div');
					row.style.display = 'flex';
					row.style.gap = '10px';
					row.style.alignItems = 'center';
					row.style.flexWrap = 'nowrap';

					const label = document.createElement('label');
					label.textContent = 'Area ' + (idx + 1) + ' (' + id + ')';
					label.style.maxWidth = 'fit-content';
					label.style.textWrap = 'nowrap';

					const current = Math.max(1, Math.min(<?php echo (int) self::MAX_COLS; ?>, parseInt(saved[id] || 1, 10) || 1));
					const sel = mkSpanSelect(current);
					sel.dataset.areaId = id;

					row.appendChild(label);
					row.appendChild(sel);
					listEl.appendChild(row);
				});
			}

			function saveAllSpans(){
				const spans = {};
				listEl.querySelectorAll('select[data-area-id]').forEach(s => {
					spans[s.dataset.areaId] = parseInt(s.value, 10) || 1;
				});

				const fd = new FormData();
				fd.append('action','fwe_dash_area_grid_save_spans');
				fd.append('nonce', spanWrap.dataset.nonce || '');
				fd.append('spans', JSON.stringify(spans));

				fetch(ajaxurl, { method:'POST', body: fd, credentials:'same-origin' })
					.then(() => window.location.reload())
					.catch(() => window.location.reload());
			}

			// IMPORTANT: Screen Options HTML appears before the dashboard markup,
			// so we wait until DOM is ready, then discover areas.
			if(document.readyState === 'loading'){
				document.addEventListener('DOMContentLoaded', renderAreaSpans, { once: true });
			} else {
				renderAreaSpans();
			}

			listEl.addEventListener('change', function(e){
				const t = e.target;
				if(!t || t.tagName !== 'SELECT' || !t.dataset.areaId) return;
				saveAllSpans();
			});
		})();
		</script>
		<?php

		return $settings . ob_get_clean();
	}

	public static function enqueue( $hook ) {
		if ( $hook !== 'index.php' ) { return; }

		$cols = (int) apply_filters( 'fwe_dash_area_grid_cols', self::get_cols() );
		$cols = max( self::MIN_COLS, min( self::MAX_COLS, $cols ) );

		$gap       = apply_filters( 'fwe_dash_area_grid_gap', '12px' );
		$stack_gap = apply_filters( 'fwe_dash_area_stack_gap', '12px' );

		$user_spans = self::get_user_spans();
		$dev_spans  = apply_filters( 'fwe_dash_area_grid_spans', [] ); // keyed by container id => int span

		$span_css = self::spans_to_css_vars( $dev_spans ) . "\n" . self::spans_to_css_vars( $user_spans );

		$css = "
/* ===== Dashboard AREA grid (drop zones) ===== */

#dashboard-widgets{
	display: grid;
	grid-template-columns: repeat({$cols}, minmax(0, 1fr));
	gap: {$gap};
	align-items: start;
}

#dashboard-widgets .postbox-container{
	float: none !important;
	width: auto !important;
	margin: 0 !important;

	grid-column: span var(--fwe-area-col-span, 1);
}

#dashboard-widgets .postbox-container .meta-box-sortables{
	display: flex;
	flex-direction: column;
	gap: {$stack_gap};
	min-height: 0 !important;
	padding: 0 !important;
}

#dashboard-widgets .postbox{
	width: 100%;
	margin: 0 !important;
}

@media (max-width: 782px){
	#dashboard-widgets{ grid-template-columns: 1fr; }
	#dashboard-widgets .postbox-container{ grid-column: auto !important; }
}

#dashboard-widgets .meta-box-sortables:has(.postbox) .postbox-placeholder,
#dashboard-widgets .meta-box-sortables:has(.postbox) .empty-container{
	display: none !important;
}

#dashboard-widgets .meta-box-sortables.fwe-has-box .postbox-placeholder,
#dashboard-widgets .meta-box-sortables.fwe-has-box .empty-container{
	display: none !important;
}

{$span_css}
";
		wp_add_inline_style( 'wp-admin', $css );

		$js = "
(function(){
	const dash = document.getElementById('dashboard-widgets');
	if(!dash) return;

	function updateAll(){
		dash.querySelectorAll('.meta-box-sortables').forEach(list => {
			const hasBox = !!list.querySelector('.postbox');
			list.classList.toggle('fwe-has-box', hasBox);
		});
	}
	updateAll();

	const mo = new MutationObserver(updateAll);
	mo.observe(dash, { childList:true, subtree:true });

	if(window.jQuery){
		jQuery(document).on('sortstop sortreceive sortremove', updateAll);
	}
})();";
		wp_add_inline_script( 'jquery-core', $js );
	}

	public static function ajax_save_cols() {
		check_ajax_referer( 'fwe_dash_area_grid_cols', 'nonce' );
		$cols = isset( $_POST['cols'] ) ? (int) $_POST['cols'] : self::default_cols();
		$cols = max( self::MIN_COLS, min( self::MAX_COLS, $cols ) );
		update_user_meta( get_current_user_id(), self::META_COLS, $cols );
		wp_send_json_success( [ 'cols' => $cols ] );
	}

	public static function ajax_save_spans() {
		check_ajax_referer( 'fwe_dash_area_grid_spans', 'nonce' );

		$raw  = isset( $_POST['spans'] ) ? wp_unslash( $_POST['spans'] ) : '';
		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) ) {
			wp_send_json_error( [ 'message' => 'invalid spans' ], 400 );
		}

		$clean = [];
		foreach ( $data as $id => $span ) {
			$id = sanitize_key( (string) $id );
			$span = max( 1, min( self::MAX_COLS, (int) $span ) );
			if ( $id ) {
				$clean[ $id ] = $span;
			}
		}

		update_user_meta( get_current_user_id(), self::META_SPANS, $clean );
		wp_send_json_success( [ 'spans' => $clean ] );
	}

	private static function default_cols() : int {
		$default = (int) apply_filters( 'fwe_dash_area_grid_default_cols', 3 );
		return max( self::MIN_COLS, min( self::MAX_COLS, $default ) );
	}

	private static function get_cols() : int {
		$val = (int) get_user_meta( get_current_user_id(), self::META_COLS, true );
		if ( $val < self::MIN_COLS || $val > self::MAX_COLS ) { $val = self::default_cols(); }
		return $val;
	}

	private static function get_user_spans() {
		return get_user_meta( get_current_user_id(), self::META_SPANS, true );
	}

	private static function spans_to_css_vars( $spans ) : string {
		if ( ! is_array( $spans ) ) { return ''; }

		$out = '';
		foreach ( $spans as $id => $span ) {
			$id = sanitize_key( (string) $id );
			$span = max( 1, min( self::MAX_COLS, (int) $span ) );
			if ( ! $id ) { continue; }
			$out .= "#dashboard-widgets #{$id}{--fwe-area-col-span: {$span};}\n";
		}
		return $out;
	}
}

FWE_Dashboard_Area_Grid_V2::init();