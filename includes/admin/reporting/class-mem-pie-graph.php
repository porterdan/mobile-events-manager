<?php
/**
 * Graphs
 *
 * This class handles building pretty report graphs
 *
 * @package MEM
 * @subpackage Admin/Reports
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MEM_Graph Class
 *
 * @since 1.4
 */
class MEM_Pie_Graph extends MEM_Graph {

	/*
	Simple example:

	data format for each point: array( location on x, location on y )

	$data = array(
		array( 'Label' => 'value' ),
		array( 'Label 2' => 'value 2' ),
	);

	$graph = new MEM_Pie_Graph( $data );
	$graph->display();

	*/

	/**
	 * Data to graph
	 *
	 * @var arr
	 * @since 1.4
	 */
	private $data;

	/**
	 * Unique ID for the graph
	 *
	 * @var str
	 * @since 1.4
	 */
	private $id = '';

	/**
	 * Graph options
	 *
	 * @var arr
	 * @since 1.4
	 */
	private $options = array();

	/**
	 * Get things started
	 *
	 * @since 1.4
	 */
	public function __construct( $_data, $options = array() ) {

		$this->data = $_data;
		// Set this so filters recieving $this can quickly know if it's a graph they want to modify
		$this->type = 'pie';

		// Generate unique ID, add 'a' since md5 can leave a numerical first character
		$this->id = 'a' . md5( rand() );

		// Setup default options;
		$defaults = array(
			'radius'           => 1,
			'legend'           => true,
			'legend_formatter' => false,
			'legend_columns'   => 3,
			'legend_position'  => 's',
			'show_labels'      => false,
			'label_threshold'  => 0.01,
			'label_formatter'  => 'memLabelFormatter',
			'label_bg_opacity' => 0.75,
			'label_radius'     => 1,
			'height'           => '300',
			'hoverable'        => true,
			'clickable'        => false,
			'threshold'        => false,
		);

		$this->options = wp_parse_args( $options, $defaults );

		add_action( 'mem_graph_load_scripts', array( $this, 'load_additional_scripts' ) );

	} // __construct

	/**
	 * Load the graphing library script
	 *
	 * @since 1.4
	 */
	public function load_additional_scripts() {
		// Use minified libraries if SCRIPT_DEBUG is turned off
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		wp_enqueue_script( 'jquery-flot-pie', MEM_PLUGIN_URL . '/assets/js/jquery.flot.pie' . $suffix . '.js' );
	} // load_additional_scripts

	/**
	 * Build the graph and return it as a string
	 *
	 * @var arr
	 * @since 1.4
	 * @return str
	 */
	public function build_graph() {

		if ( count( $this->data ) ) {
			$this->load_scripts();

			ob_start();
			?>
			<script type="text/javascript">
				var <?php echo $this->id; ?>_data = [
				<?php foreach ( $this->data as $label => $value ) : ?>
					<?php echo '{ label: "' . esc_attr( $label ) . '", data: "' . $value . '" },' . "\n"; ?>
				<?php endforeach; ?>
				];

				var <?php echo $this->id; ?>_options = {
					series: {
						pie: {
							show: true,
							radius: <?php echo $this->options['radius']; ?>,
							label: [],
						},
						mem_vars: {
							id: '<?php echo $this->id; ?>',
						}
					},
					legend: {
						show: <?php echo $this->options['legend']; ?>,
					},
					grid: {},
				};

				<?php if ( true === $this->options['show_labels'] ) : ?>
					<?php echo $this->id; ?>_options.series.pie.label.show = true;
					<?php echo $this->id; ?>_options.series.pie.label.formatter = <?php echo $this->options['label_formatter']; ?>;
					<?php echo $this->id; ?>_options.series.pie.label.threshold = <?php echo $this->options['label_threshold']; ?>;
					<?php echo $this->id; ?>_options.series.pie.label.radius = <?php echo $this->options['label_radius']; ?>;
					<?php echo $this->id; ?>_options.series.pie.label.background = { opacity: <?php echo $this->options['label_bg_opacity']; ?> };
				<?php endif; ?>

				<?php if ( true === $this->options['legend'] && ! empty( $this->options['legend_formatter'] ) ) : ?>
					<?php echo $this->id; ?>_options.legend.labelFormatter = <?php echo $this->options['legend_formatter']; ?>;
					<?php echo $this->id; ?>_options.legend.noColumns = <?php echo $this->options['legend_columns']; ?>;
					<?php echo $this->id; ?>_options.legend.position = "<?php echo $this->options['legend_position']; ?>";
				<?php endif; ?>

				<?php if ( true === $this->options['hoverable'] ) : ?>
					<?php echo $this->id; ?>_options.grid.hoverable = true;
				<?php endif; ?>

				<?php if ( true === $this->options['clickable'] ) : ?>
					<?php echo $this->id; ?>_options.grid.clickable = true;
				<?php endif; ?>

				jQuery( document ).ready( function($) {
					var <?php echo $this->id; ?>Chart = $('#mem-pie-graph-<?php echo $this->id; ?>');
					$.plot( <?php echo $this->id; ?>Chart, <?php echo $this->id; ?>_data, <?php echo $this->id; ?>_options );
					<?php if ( ! wp_is_mobile() ) : ?>
					$(<?php echo $this->id; ?>Chart).on('plothover', function (event, pos, item) {
						$('.mem-legend-item-wrapper').css('background-color', 'inherit');
						if ( item ) {
							var label = item.series.label;
							var id = item.series.mem_vars.id;

							var slug = label.toLowerCase().replace(/\s/g, '-');
							var legendTarget = '#' + id + slug;

							$('.mem-legend-item-wrapper' + legendTarget).css('background-color', '#f0f0f0');
						}
					});
					<?php endif; ?>
				});

			</script>
			<div class="mem-pie-graph-wrap">
				<div id="mem-pie-graph-<?php echo $this->id; ?>" class="mem-pie-graph" style="height: <?php echo $this->options['height']; ?>px;"></div>
				<div id="mem-pie-legend-<?php echo $this->id; ?>" class="mem-pie-legend"></div>
			</div>
			<?php
		}
		return apply_filters( 'mem_pie_graph_output', ob_get_clean(), $this->id, $this->data, $this->options );

	} // build_graph

} // MEM_Pie_Graph
