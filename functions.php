function get_all_orders_meta() {
    if (!is_admin()) {
        return;
    }

    if (!isset($_GET['download_orders']) || $_GET['download_orders'] !== 'true') {
        return;
    }

    $batch_size = 5000; // Define the batch size
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0; // Get offset from query parameters

    // Start output buffering
    ob_start();

    // Prepare the CSV header
    $csv_header = ['Order ID','Order Date', 'Parent First Name', 'Parent Last Name', 'Ref. First Name', 'Ref. Last Name', 'DOB Month', 'DOB Day', 'DOB Year', 'Gender', 'Medical Allergies', 'Referral'];
    $header_row = implode(',', $csv_header) . "\n";

    // Output the CSV header
    echo $header_row;

    // Retrieve orders in batches
	$orders = wc_get_orders(array(
		'limit'  => $batch_size,
		'offset' => $offset,
		'status' => array('completed', 'processing')
	));


    foreach ($orders as $order) {
        // Get billing details
     	 $order_date = $order->get_date_created()->date('Y-m-d');
		
		if (method_exists($order, 'get_billing_first_name') && method_exists($order, 'get_billing_last_name')) {
			$billing_first_name = $order->get_billing_first_name();
			$billing_last_name = $order->get_billing_last_name();

			if (!empty($billing_first_name) && !empty($billing_last_name)) {
				// Both first and last names are present
				$billing_first_name = $order->get_billing_first_name();
				$billing_last_name = $order->get_billing_last_name();
			} else {
				// One or both names are missing
				$billing_first_name = "None";
				$billing_last_name = "None";
			}
		} else {
			// Methods do not exist
			$billing_first_name = "None";
			$billing_last_name = "None";
		}
        
        

        // Get order items
        $items = $order->get_items();

        foreach ($items as $item_id => $item) {
            $quantity = $item->get_quantity();

            for ($i = 1; $i <= $quantity; $i++) {
                // Get the player data
                $playersData = wc_get_order_item_meta($item_id, 'player_' . $i);

                // Check if the player data contains 'referral' in 'hdyhau'
                if (is_array($playersData) && isset($playersData['hdyhau']) && $playersData['hdyhau'] === 'referral' && $playersData['referral_first_name'] ) {
					
					//var_dump($playersData);
					
                    $csv_row = [
                        $order->get_id(),
                        $order_date,
                        $billing_first_name,
                        $billing_last_name,
                        $playersData['referral_first_name'] ?? '',
                        $playersData['referral_last_name'] ?? '',
                        $playersData['dob']['month'] ?? '',
                        $playersData['dob']['day'] ?? '',
                        $playersData['dob']['year'] ?? '',
                        $playersData['gender'] ?? '',
                        $playersData['medicalallergies'] ?? '',
                        $playersData['hdyhau']
                    ];

                    // Output the CSV row
                    echo implode(',', $csv_row) . "\n";
                }
            }
        }
    }

    // Move to the next batch
    $next_offset = $offset + $batch_size;

    // Output the pagination link
    echo '<a href="?download_orders=true&offset=' . $next_offset . '">Download Next Batch</a>';

    // Capture the CSV output
    $csv_output = ob_get_clean();


    // Output the CSV content
    echo $csv_output;
    exit;
}

// Comment out this line to prevent immediate execution
 add_action('admin_init', 'get_all_orders_meta');
