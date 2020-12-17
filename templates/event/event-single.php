<?php
/**
 * This template is used to display the details of a single event to the client.
 *
 * @version     1.0.1
 * @author Jack Mawhinney, Dan Porter
 * @since     1.3
 * @content_tag   {client_*}
 * @content_tag   {event_*}
 * @shortcodes    Supported
 *
 * Do not customise this file!
 * If you wish to make changes, copy this file to your theme directory /theme/tmem-templates/event/event-single.php
 */
global $tmem_event;
?>
<?php do_action( 'tmem_pre_event_detail', $tmem_event->ID, $tmem_event ); ?>
<div id="post-<?php echo $tmem_event->ID; ?>" class="tmem-s-event tmem-<?php echo $tmem_event->post_status; ?>">
  
  <?php do_action( 'tmem_print_notices' ); ?>

  <p><?php printf( __( 'Details of your %s taking place on %s are shown below.', 'mobile-events-manager' ),
  tmem_get_label_singular( true ), '{event_date}' ); ?></p>

  <p><?php printf( __( 'Please confirm the details displayed are correct or <a href="%s">contact us</a> with any adjustments.', 'mobile-events-manager' ),
  '{contact_page}' ); ?></p>

  <?php
  /**
   * Display event action buttons
   */
  ?>
  <div class="tmem-action-btn-container">{event_action_buttons}</div>

  <?php
  /**
   * Display event details
   */
  ?>
  <?php do_action( 'tmem_pre_event_details', $tmem_event->ID, $tmem_event ); ?>

  <div id="tmem-singleevent-details">
    <div class="single-event-field full">
      <div class="tmem-event-heading">{event_name} - {event_date}</div>
    </div>


    <div class="tmem-singleevent-overview">

      <div class="single-event-field half">     
        <strong> <?php _e( 'Status:', 'mobile-events-manager' ); ?></strong> {event_status}
      </div>

      <div class="single-event-field half">     
        <strong><?php printf( __( 'Function: ', 'mobile-events-manager' ), tmem_get_label_singular() ); ?></strong> {event_type}
      </div>

      <div class="single-event-field half">     
        <strong><?php _e( 'Function Starts: ', 'mobile-events-manager' ); ?></strong> {start_time}
      </div>
      <div class="single-event-field half">     
        <strong><?php _e( 'Function Ends: ', 'mobile-events-manager' ); ?></strong> {end_time} ({end_date})
      </div>

      <div class="single-event-field full">     
        <div class="tmem-heading">Pricing</div>
      </div>


      <div class="single-event-field half">     
        <strong><?php _e( 'Total Cost:', 'mobile-events-manager' ); ?></strong> {total_cost}<br />
        <strong>{deposit_label}:</strong> {deposit} ({deposit_status})<br />
        <strong>{balance_label} <?php _e( 'Remaining', 'mobile-events-manager' ); ?>:</strong> {balance}
      </div>

      <div class="single-event-field full">     
        <div class="tmem-heading"><?php _e( 'Your Details', 'mobile-events-manager' ); ?></div>
      </div>

      <div class="single-event-field half">     
        <strong><?php _e( 'Your Name: ', 'mobile-events-manager' ); ?></strong> {client_fullname}
      </div>

      <div class="single-event-field half">     
        <strong><?php _e( 'Phone:', 'mobile-events-manager' ); ?></strong> {client_primary_phone}
      </div>

      <div class="single-event-field half">     
        <strong><?php _e( 'Email: ', 'mobile-events-manager' ); ?></strong> {client_email}
      </div>

      <div class="single-event-field half">     
        <strong><?php _e( 'Address: ', 'mobile-events-manager' ); ?></strong> {client_full_address}
      </div>

      <div class="single-event-field full">     
        <div class="tmem-heading"><?php _e( 'Venue Details', 'mobile-events-manager' ); ?></div>
      </div>

      <div class="single-event-field half">     
        <strong><?php _e( 'Venue: ', 'mobile-events-manager' ); ?></strong> {venue}
      </div>

      <div class="single-event-field half">     
        <strong><?php _e( 'Address: ', 'mobile-events-manager' ); ?></strong> {venue_full_address}
      </div>

      <div class="single-event-field full">     
        <div class="tmem-heading"><?php _e( 'Function Notes', 'mobile-events-manager' ); ?></div>
        {client_notes}
      </div>

    </div>

  </div>
  <?php do_action( 'tmem_post_event_details', $tmem_event->ID, $tmem_event ); ?>