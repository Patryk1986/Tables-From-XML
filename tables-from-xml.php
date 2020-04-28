<?php
/**
 * Plugin Name: Tables From XML
 * Plugin URI: https://patryksawicki.pl/wp-plugins/tables-from-xml
 * Description: Create table from XML file.
 * Version: 2.0
 * Author: Patryk Sawicki
 * Author URI: https://patryksawicki.pl
 */

define('TFXURL',    plugins_url('', __FILE__));

/*Shortcode*/
add_shortcode('tfx_table', 'tfx_table');

/*Inicjalizacja*/
add_action('admin_init', 'tfx_init');
function tfx_init(){
	/*Rejestracja ustawień*/
	register_setting('tfx_options', 'tfx_options');
	register_setting('tfx_options', 'tfx_tables');
	register_setting('tfx_options', 'tfx_last_update');
	$options=get_option('tfx_options');

	/*Sekcje*/

	/* - Settings*/
	add_settings_section('tables-from-xml_main', __('Settings', 'tables-from-xml'), 'tfx_main_options', 'tables-from-xml');
	/* - Table Settings*/
	if(isset($options['url']) && !empty($options['url']))
	{
		add_settings_section('tables-from-xml_table_settings', __('Table Settings', 'tables-from-xml'), 'tfx_table_settings', 'tables-from-xml');
		add_settings_section('tables-from-xml_load_section', __('Load Data', 'tables-from-xml'), 'tfx_table_load_section', 'tables-from-xml');
	}

	/*Pola*/

	/* - Settings*/
	add_settings_field('tables-from-xml_main_url', __('URL:', 'tables-from-xml'), 'tfx_url_field', 'tables-from-xml', 'tables-from-xml_main');
	add_settings_field('tables-from-xml_main_route_in_file', __('Route in file:', 'tables-from-xml'), 'tfx_route_in_file_field', 'tables-from-xml', 'tables-from-xml_main');
	add_settings_field('tables-from-xml_main_daily', __('Daily:', 'tables-from-xml'), 'tfx_daily_field', 'tables-from-xml', 'tables-from-xml_main');
	add_settings_field('tables-from-xml_main_time', __('Time:', 'tables-from-xml'), 'tfx_time_field', 'tables-from-xml', 'tables-from-xml_main');
	add_settings_field('tables-from-xml_main_cells_count', __('Cells Count:', 'tables-from-xml'), 'tfx_cells_count_field', 'tables-from-xml', 'tables-from-xml_main');
	add_settings_field('tables-from-xml_main_tables_count', __('Tables Count:', 'tables-from-xml'), 'tfx_tables_count_field', 'tables-from-xml', 'tables-from-xml_main');
	add_settings_field('tables-from-xml_main_save', '', 'tfx_save_field', 'tables-from-xml', 'tables-from-xml_main');

	/* - Table Settings*/
	if(isset($options['url']) && !empty($options['url']))
	{
		if(isset($options['cells_count']) && $options['cells_count']>0) {
			add_settings_field( 'tables-from-xml_table_fields', __( 'Cells:', 'tables-from-xml' ), 'tfx_table_fields', 'tables-from-xml', 'tables-from-xml_table_settings' );
		}
		if(isset($options['tables_count']) && $options['tables_count']>0) {
			add_settings_field( 'tables-from-xml_table_options', __( 'Tables:', 'tables-from-xml' ), 'tfx_table_options', 'tables-from-xml', 'tables-from-xml_table_settings' );
		}
		add_settings_field('tables-from-xml_main_save', '', 'tfx_save_field', 'tables-from-xml', 'tables-from-xml_table_settings');

		if(isset($_GET['load']) && $_GET['load']==1)
			add_settings_field('tables-from-xml_main_load_results', __('Results', 'tables-from-xml'), 'tfx_load_url', 'tables-from-xml', 'tables-from-xml_load_section');
		add_settings_field('tables-from-xml_main_load', '', 'tfx_load', 'tables-from-xml', 'tables-from-xml_load_section');
	}

	/*Cron*/
	add_action('tfx_cron', 'tfx_load_url');
	$nextScheduled=wp_next_scheduled('tfx_cron');
	if(!is_null($nextScheduled))
		wp_unschedule_event($nextScheduled, 'tfx_cron');
	if(isset($options['daily']) && $options['daily']==1 && isset($options['time']) && !is_null($options['time']))
		wp_schedule_event(strtotime($options['time']), 'daily', 'tfx_cron');
//		wp_schedule_event(strtotime('tomorrow'), 'daily', 'tfx_cron');
//		wp_schedule_event(date('Y-m-d H:i:s', strtotime('tomorrow')), 'daily', 'tfx_cron');
}

/*Aktywacja*/
register_activation_hook( __FILE__, 'tfx_activate' );
function tfx_activate(){
	$options=get_option('tfx_options');

	if(is_null($options) || !isset($options['url'])) {
		$tfx_options = [
			'url'           => null,
			'route_in_file' => null,
			'daily'         => false,
			'time'          => '04:00',
			'cells_count'   => 5,
			'tables_count'  => 5,
		];

		update_option( 'tfx_options', $tfx_options );
	}
}

/*Dezaktywacja*/
register_deactivation_hook( __FILE__, 'tfx_deactivate' );
function tfx_deactivate(){
	/*$tfx_options=[
		'daily'=>false,
	];

	update_option('tfx_options', $tfx_options);*/
}

/*Menu*/
add_action( 'admin_menu', 'tables_from_xml_menu' );
function tables_from_xml_menu() {
	add_options_page( __('Tables From XML - Options', 'tables-from-xml'),
	                  '<img class="menu_pto" src="'.TFXURL.'/images/icon.png" alt="" />'.__('Tables From XML', 'tables-from-xml'),
	                  'manage_options',
	                  'tables_from_xml',
	                  'tables_from_xml_options');
}

/*Strona ustawień*/
function tables_from_xml_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	echo '<div class="wrap">';
		echo '<h1>'.__('Tables From XML', 'tables-from-xml').'</h1>';
		echo '<p>'.__('Enter the plugin settings.', 'tables-from-xml').'</p>';

		echo '<form method="post" action="options.php">';
			settings_fields('tfx_options');
			do_settings_sections('tables-from-xml');
		echo '</form>';

		echo '<form method="get" action="" name="load_data" id="load_data">';
			echo "<input type='hidden' name='page' value='tables_from_xml' />";
			echo "<input type='hidden' name='load' value='1' />";
		echo '</form>';
	echo '</div>';
}

/*Sekcje*/
function tfx_main_options(){
	echo '<p>'.__('Set plugin settings', 'tables-from-xml').'</p>';
}
function tfx_table_settings(){
	echo '<p>'.__('Set table settings', 'tables-from-xml').'</p>';
}
function tfx_table_load_section(){
	echo '<p>'.__('Load data from URL', 'tables-from-xml').'</p>';
}

/*Pola*/
function tfx_url_field(){
	$url=get_option('tfx_options')['url'] ?? '';
	echo "<input id='url' name='tfx_options[url]' type='url' value='$url' placeholder='".__('Enter URL to XML file.', 'tables-from-xml')."' required />";
}
function tfx_route_in_file_field(){
	$route=get_option('tfx_options')['route_in_file'] ?? '';
	echo "<input id='route_in_file' name='tfx_options[route_in_file]' type='text' value='$route' placeholder='".__('Fields separated by comma.', 'tables-from-xml')."' required />";
}
function tfx_daily_field(){
	$daily=get_option('tfx_options')['daily']==1 ? 'checked' : '';
	echo "<input id='daily' name='tfx_options[daily]' type='checkbox' value='1' $daily /> ";
	echo "<label for='daily'>".__('Should the file be checked daily?', 'tables-from-xml')."</label>";
}
function tfx_time_field(){
	$time=get_option('tfx_options')['time'] ?? '';
	echo "<input id='time' name='tfx_options[time]' type='time' value='$time' /> ";
	echo "<label for='time'>".__('Enter the time to daily check. Current server time is: ', 'tables-from-xml').'<b>'.date('H:i')."</b></label>";
}
function tfx_cells_count_field(){
	$cellsCount=get_option('tfx_options')['cells_count'] ?? '';
	echo "<input id='cells_count' name='tfx_options[cells_count]' type='number' value='$cellsCount' min='1' required /> ";
	echo "<label for='cells_count'>".__('Enter max count of cells that you want to extract from xml file.', 'tables-from-xml')."</label>";
}
function tfx_tables_count_field(){
	$cellsCount=get_option('tfx_options')['tables_count'] ?? '';
	echo "<input id='tables_count' name='tfx_options[tables_count]' type='number' value='$cellsCount' min='1' required /> ";
	echo "<label for='tables_count'>".__('Enter the number of tables to create.', 'tables-from-xml')."</label>";
}
function tfx_save_field(){
	echo "<input type='submit' name='submit' value='".__('Save Changes', 'tables-from-xml')."' />";
	echo '<hr />';
}
function tfx_load(){
	echo "<input type='submit' form='load_data' value='".__('Load Data', 'tables-from-xml')."' />";
	echo '<hr />';
}
function tfx_table_fields(){
	$options=get_option('tfx_options');

	for($i=0; $i<$options['cells_count']; $i++)
	{
		echo "<b>".__('Cell', 'tables-from-xml')." $i:</b><br />";

		echo "<input id='table_fields_{$i}_name' name='tfx_options[table_field_{$i}_name]' type='text' value='".($options['table_field_'.$i.'_name'] ?? '')."' placeholder='".__('Name in XML:', 'tables-from-xml')."' /> ";
		echo "<input id='table_fields_{$i}_name_desktop' name='tfx_options[table_field_{$i}_desktop]' type='text' value='".($options['table_field_'.$i.'_desktop'] ?? '')."' placeholder='".__('Name for desktop:', 'tables-from-xml')."' /> ";
		echo "<input id='table_fields_{$i}_name_mobile' name='tfx_options[table_field_{$i}_mobile]' type='text' value='".($options['table_field_'.$i.'_mobile'] ?? '')."' placeholder='".__('Name for mobile:', 'tables-from-xml')."' /> ";
		echo "<input id='table_fields_{$i}_round' name='tfx_options[table_field_{$i}_round]' type='number' value='".($options['table_field_'.$i.'_round'] ?? '')."' placeholder='".__('Round for numeric:', 'tables-from-xml')."' /> ";
		echo "<input id='table_fields_{$i}_after_value' name='tfx_options[table_field_{$i}_after_value]' type='text' value='".($options['table_field_'.$i.'_after_value'] ?? '')."' placeholder='".__('Text after value:', 'tables-from-xml')."' /> ";
		echo "<input id='table_fields_{$i}_id_change' name='tfx_options[table_field_{$i}_id_change]' type='text' value='".($options['table_field_'.$i.'_id_change'] ?? '')."' placeholder='".__('id=text|class;id2=text|class', 'tables-from-xml')."' /> ";
		echo "<input id='table_fields_{$i}_type' name='tfx_options[table_field_{$i}_type]' type='text' value='".($options['table_field_'.$i.'_type'] ?? '')."' placeholder='".__('Type of field:', 'tables-from-xml')."' /> ";
		echo "<input id='table_fields_{$i}_empty_if' name='tfx_options[table_field_{$i}_empty_if]' type='text' value='".($options['table_field_'.$i.'_empty_if'] ?? '')."' placeholder='".__('Empty if field == 0:', 'tables-from-xml')."' /> ";

		echo "<br /><br />";
	}
}
function tfx_table_options(){
	$options=get_option('tfx_options');

	for($i=0; $i<$options['tables_count']; $i++)
	{
		echo "<b>".__('Table', 'tables-from-xml')." $i:</b> <code>[tfx_table id={$i}]</code><br />";

		echo "<input id='table_option_{$i}_name' name='tfx_options[table_option_{$i}_name]' type='text' value='".($options['table_option_'.$i.'_name'] ?? '')."' placeholder='".__('Name:', 'tables-from-xml')."' /> ";
		echo "<input id='table_option_{$i}_id_name' name='tfx_options[table_option_{$i}_id_name]' type='text' value='".($options['table_option_'.$i.'_id_name'] ?? '')."' placeholder='".__('Id name in XML:', 'tables-from-xml')."' /> ";
		echo "<input id='table_option_{$i}_id' name='tfx_options[table_option_{$i}_id]' type='text' value='".($options['table_option_'.$i.'_id'] ?? '')."' placeholder='".__('Id:', 'tables-from-xml')."' /> ";
		echo "<input id='table_option_{$i}_row_url' name='tfx_options[table_option_{$i}_row_url]' type='url' value='".($options['table_option_'.$i.'_row_url'] ?? '')."' placeholder='".__('Row URL:', 'tables-from-xml')."' /> ";

		echo "<br /><br />";
	}
}

/*Funkcja skrótów*/
function tfx_table($attr)
{
	if(isset($attr['id']))
	{
		$options=get_option('tfx_options');
		if(isset($options['daily']) && $options['daily']==1)
		{
//			$lastUpdate=get_option('tfx_last_update');
//			if(empty($lastUpdate) || $lastUpdate<strtotime(date('Y-m-d').' 00:00:00'))
				tfx_load_url();
		}

		$rows=get_option('tfx_tables');

		$names=[];
		for($j=0; $j<$options['cells_count']; $j++)
		{
			$row=[];
			$row['name']=$options["table_field_{$j}_name"];
			$row['desktop']=$options["table_field_{$j}_desktop"];
			$row['mobile']=$options["table_field_{$j}_mobile"];
			$row['round']=$options["table_field_{$j}_round"];
			$row['after_value']=$options["table_field_{$j}_after_value"];
			$row['id_change']=$options["table_field_{$j}_id_change"];
			$row['type']=$options["table_field_{$j}_type"];
			$row['empty_if']=$options["table_field_{$j}_empty_if"];
			$names[]=$row;
		}

		$rowUrl=$options['table_option_'.$attr['id'].'_row_url'] ?? null;
		if(!is_null($rowUrl))
			[$rowUrl, $rowUrlId]=explode('=', $rowUrl);

		$style="
		<style>
		.table {
		  width: 100%;
		  max-width: 100%;
		  margin-bottom: 20px;
		}
		.table > thead > tr > th,
		.table > tbody > tr > th,
		.table > tfoot > tr > th,
		.table > thead > tr > td,
		.table > tbody > tr > td,
		.table > tfoot > tr > td {
		  padding: 8px;
		  line-height: 1.42857143;
		  vertical-align: top;
		  border-top: 1px solid #ddd;
		}
		.table > thead > tr > th {
		  vertical-align: bottom;
		  border-bottom: 2px solid #ddd;
		}
		.table > caption + thead > tr:first-child > th,
		.table > colgroup + thead > tr:first-child > th,
		.table > thead:first-child > tr:first-child > th,
		.table > caption + thead > tr:first-child > td,
		.table > colgroup + thead > tr:first-child > td,
		.table > thead:first-child > tr:first-child > td {
		  border-top: 0;
		}
		.table > tbody + tbody {
		  border-top: 2px solid #ddd;
		}
		.table .table {
		  background-color: #fff;
		}
		.table-condensed > thead > tr > th,
		.table-condensed > tbody > tr > th,
		.table-condensed > tfoot > tr > th,
		.table-condensed > thead > tr > td,
		.table-condensed > tbody > tr > td,
		.table-condensed > tfoot > tr > td {
		  padding: 5px;
		}
		.table-bordered {
		  border: 1px solid #ddd;
		}
		.table-bordered > thead > tr > th,
		.table-bordered > tbody > tr > th,
		.table-bordered > tfoot > tr > th,
		.table-bordered > thead > tr > td,
		.table-bordered > tbody > tr > td,
		.table-bordered > tfoot > tr > td {
		  border: 1px solid #ddd;
		}
		.table-bordered > thead > tr > th,
		.table-bordered > thead > tr > td {
		  border-bottom-width: 2px;
		}
		.table-striped > tbody > tr:nth-of-type(odd) {
		  background-color: #f9f9f9;
		}
		.table-hover > tbody > tr:hover {
		  background-color: #f5f5f5;
		}
		table col[class*=\"col-\"] {
		  position: static;
		  display: table-column;
		  float: none;
		}
		table td[class*=\"col-\"],
		table th[class*=\"col-\"] {
		  position: static;
		  display: table-cell;
		  float: none;
		}
		.table > thead > tr > td.active,
		.table > tbody > tr > td.active,
		.table > tfoot > tr > td.active,
		.table > thead > tr > th.active,
		.table > tbody > tr > th.active,
		.table > tfoot > tr > th.active,
		.table > thead > tr.active > td,
		.table > tbody > tr.active > td,
		.table > tfoot > tr.active > td,
		.table > thead > tr.active > th,
		.table > tbody > tr.active > th,
		.table > tfoot > tr.active > th {
		  background-color: #f5f5f5;
		}
		.table-hover > tbody > tr > td.active:hover,
		.table-hover > tbody > tr > th.active:hover,
		.table-hover > tbody > tr.active:hover > td,
		.table-hover > tbody > tr:hover > .active,
		.table-hover > tbody > tr.active:hover > th {
		  background-color: #e8e8e8;
		}
		.table > thead > tr > td.success,
		.table > tbody > tr > td.success,
		.table > tfoot > tr > td.success,
		.table > thead > tr > th.success,
		.table > tbody > tr > th.success,
		.table > tfoot > tr > th.success,
		.table > thead > tr.success > td,
		.table > tbody > tr.success > td,
		.table > tfoot > tr.success > td,
		.table > thead > tr.success > th,
		.table > tbody > tr.success > th,
		.table > tfoot > tr.success > th {
		  background-color: #dff0d8;
		}
		.table-hover > tbody > tr > td.success:hover,
		.table-hover > tbody > tr > th.success:hover,
		.table-hover > tbody > tr.success:hover > td,
		.table-hover > tbody > tr:hover > .success,
		.table-hover > tbody > tr.success:hover > th {
		  background-color: #d0e9c6;
		}
		.table > thead > tr > td.info,
		.table > tbody > tr > td.info,
		.table > tfoot > tr > td.info,
		.table > thead > tr > th.info,
		.table > tbody > tr > th.info,
		.table > tfoot > tr > th.info,
		.table > thead > tr.info > td,
		.table > tbody > tr.info > td,
		.table > tfoot > tr.info > td,
		.table > thead > tr.info > th,
		.table > tbody > tr.info > th,
		.table > tfoot > tr.info > th {
		  background-color: #d9edf7;
		}
		.table-hover > tbody > tr > td.info:hover,
		.table-hover > tbody > tr > th.info:hover,
		.table-hover > tbody > tr.info:hover > td,
		.table-hover > tbody > tr:hover > .info,
		.table-hover > tbody > tr.info:hover > th {
		  background-color: #c4e3f3;
		}
		.table > thead > tr > td.warning,
		.table > tbody > tr > td.warning,
		.table > tfoot > tr > td.warning,
		.table > thead > tr > th.warning,
		.table > tbody > tr > th.warning,
		.table > tfoot > tr > th.warning,
		.table > thead > tr.warning > td,
		.table > tbody > tr.warning > td,
		.table > tfoot > tr.warning > td,
		.table > thead > tr.warning > th,
		.table > tbody > tr.warning > th,
		.table > tfoot > tr.warning > th {
		  background-color: #fcf8e3;
		}
		.table-hover > tbody > tr > td.warning:hover,
		.table-hover > tbody > tr > th.warning:hover,
		.table-hover > tbody > tr.warning:hover > td,
		.table-hover > tbody > tr:hover > .warning,
		.table-hover > tbody > tr.warning:hover > th {
		  background-color: #faf2cc;
		}
		.table > thead > tr > td.danger,
		.table > tbody > tr > td.danger,
		.table > tfoot > tr > td.danger,
		.table > thead > tr > th.danger,
		.table > tbody > tr > th.danger,
		.table > tfoot > tr > th.danger,
		.table > thead > tr.danger > td,
		.table > tbody > tr.danger > td,
		.table > tfoot > tr.danger > td,
		.table > thead > tr.danger > th,
		.table > tbody > tr.danger > th,
		.table > tfoot > tr.danger > th {
		  background-color: #f2dede;
		}
		.table-hover > tbody > tr > td.danger:hover,
		.table-hover > tbody > tr > th.danger:hover,
		.table-hover > tbody > tr.danger:hover > td,
		.table-hover > tbody > tr:hover > .danger,
		.table-hover > tbody > tr.danger:hover > th {
		  background-color: #ebcccc;
		}
		.table-striped > tbody > tr:nth-of-type(odd) {
		  background-color: #f9f9f9;
		}
		.first-tr{
		background-color: #ff0062 !important;
		color: #fff;
		}
			.btn {
				display:inline-block;
				margin-bottom:0;
				font-weight:400;
				text-align:center;
				vertical-align:middle;
				-ms-touch-action:manipulation;
				touch-action:manipulation;
				cursor:pointer;
				background-image:none;
				border:1px solid transparent;
				white-space:nowrap;
				padding:6px 12px;
				font-size:14px;
				line-height:1.42857143;
				border-radius:4px;
				-webkit-user-select:none;
				-moz-user-select:none;
				-ms-user-select:none;
				user-select:none;
				width: 100%;
			}
			.btn-group-sm>.btn,
			.btn-sm {
				padding:5px 10px;
				font-size:12px;
				line-height:1.5;
				border-radius:3px
			}
			.btn-danger {
				color:#fff;
				background-color: #ff0062;
				border-color:#d43f3a
			}
			.btn-danger.focus,
			.btn-danger:focus {
				color:#fff;
				background-color:#df0056;
				border-color:#761c19
			}
			.btn-danger:hover {
				color:#fff;
				background-color:#df0056;
				border-color:#ac2925
			}
			.btn-success {
				color:#fff;
				background-color:#5cb85c;
				border-color:#4cae4c
			}
			.btn-success.focus,
			.btn-success:focus {
				color:#fff;
				background-color:#449d44;
				border-color:#255625
			}
			.btn-success:hover {
				color:#fff;
				background-color:#449d44;
				border-color:#398439
			}
			.btn-default {
				color:#fff;
				background-color:#878787;
				border-color:#ccc
			}
			.btn-default.focus,
			.btn-default:focus {
				color:#fff;
				background-color:#6a6a6a;
				border-color:#8c8c8c
			}
			.btn-default:hover {
				color:#fff;
				background-color:#6a6a6a;
				border-color:#adadad
			}
			@media (max-width:767px) {
				.hidden-xs {
					display:none!important
				}
				.tfx-table td{
					text-align: center;
					font-size: 12px;
				}
			}
			@media (min-width:768px) and (max-width:991px) {
				.hidden-sm {
					display:none!important
				}
			}
			@media (min-width:992px) and (max-width:1229px) {
				.hidden-md {
					display:none!important
				}
			}
			@media (min-width:1230px) {
				.hidden-lg {
					display:none!important
				}
			}
			@media (max-width:372px) {
				.tfx-table td .btn{
					padding: 5px 2px !important;
				}
			}
			@media (max-width:394px) {
				.tfx-table td{
					padding: 5px 2px !important;
				}
			}
			@media (min-width: 395px) and (max-width:420px) {
				.tfx-table td{
					padding: 5px 5px !important;
				}
			}
			.tfx-table tr
			{
				cursor: pointer;
			}
		</style>
		";
		$script="
		<script>
			function rowLink(id) {
			  window.open('$rowUrl='+id, '_blank');
			}
		</script>
		";

		$htmlTable="$style$script<table class='table tfx-table table-hover table-striped'>";

			$htmlTable.="<tr class='first-tr'>";
				foreach ($names as $name)
				{
					if(!empty($name['desktop']))
					{
						$htmlTable.="<th class='hidden-xs hidden-sm'>";
							$htmlTable.=$name['desktop'];
						$htmlTable.="</th>";
					}
					if(!empty($name['mobile']))
					{
						$htmlTable.="<th class='hidden-md hidden-lg'>";
							$htmlTable.=$name['mobile'];
						$htmlTable.="</th>";
					}
				}
			$htmlTable.="</tr>";

			usort($rows, tfx_sort());

			foreach ($rows as $row)
			{
				if($row[$options['table_option_'.$attr['id'].'_id_name']]!=$options['table_option_'.$attr['id'].'_id'])
					continue;

				$htmlTable.="<tr ".(!is_null($rowUrl) ? " onclick='rowLink(".$row[$rowUrlId].")'" : '').">";
					foreach ($names as $name)
					{
						$round=$name['round'] ?? 2;
						$afterValue=$name['after_value'] ?? '';
						$type=$name['type'] ?? '';
						$empty_if=$name['empty_if'];
						$class='';

						if(isset($name['id_change']) && !empty($name['id_change']))
						{
							$changes=[];
							foreach (explode(';', $name['id_change']) as $change)
							{
								$temp=explode('=', $change);
								$changes[$temp[0]]=$temp[1];
							}

							if(isset($changes[$row[$name['name']]]))
							{
								[$select, $class]=explode('|', $changes[$row[$name['name']]]);
								$row[$name['name']]=$select;
							}
						}
						switch ($type)
						{
							case 'button':
								$typeStart="<button class='btn btn-sm $class'>";
								$typeEnd="</button>";
								break;
							default:
								$typeStart="<span class='$class'>";
								$typeEnd="</span>";
								break;
						}

						if(!empty($name['desktop']))
						{
							$htmlTable.="<td class='hidden-xs hidden-sm'>$typeStart";
								if(!empty($empty_if) && $row[$empty_if]!=1)
									$htmlTable.='';
								elseif(is_numeric($row[$name['name']]))
									$htmlTable.=number_format($row[$name['name']], $round, ',', ' ').$afterValue;
								else
									$htmlTable.=$row[$name['name']].$afterValue;

							$htmlTable.="$typeEnd</td>";
						}
						if(!empty($name['mobile']))
						{
							$htmlTable.="<td class='hidden-md hidden-lg'>$typeStart";
								if(!empty($empty_if) && $row[$empty_if]==1)
									$htmlTable.='';
								elseif(is_numeric($row[$name['name']]))
									$htmlTable.=number_format($row[$name['name']], $round, ',', ' ').$afterValue;
								else
									$htmlTable.=$row[$name['name']].$afterValue;

							$htmlTable.="$typeEnd</td>";
						}
					}
				$htmlTable.="</tr>";
			}

		$htmlTable.="</table>";

		return $htmlTable;
	}
	return "Brak Tabeli";
}

function tfx_sort()
{
	return function ($a, $b)
	{
		if($a['ProjectNumber']!=$b['ProjectNumber'])
			return strnatcmp(strtolower($a['ProjectNumber']), strtolower($b['ProjectNumber']));
		else
			return strnatcmp(strtolower($a['Title']), strtolower($b['Title']));
	};
}

function tfx_load_url()
{
	delete_option('tfx_tables');
	$options=get_option('tfx_options');
	if(!isset($options['url']))
	{
		echo __('XML file URL is missing.', 'tables-from-xml');
		return;
	}

	$result=wp_remote_get($options['url']);

	$routes=explode(',', $options['route_in_file']);

	$xml = (array) new SimpleXMLElement($result['body']);

	foreach ($routes as $route)
		$xml=(array)($xml[$route]);

	$i=0;
	$tables=[];
	foreach ($xml as $item)
	{
		$item=(array)$item;
		$i++;

		$row=[];
		for($j=0; $j<$options['cells_count']; $j++)
		{
			$name=$options["table_field_{$j}_name"];
			if(is_string($item[$name]))
				$row[$name]=$item[$name];
		}
		$tables[]=$row;
	}

	update_option('tfx_tables', $tables);
	echo __('Loaded: ', 'tables-from-xml').$i;

	update_option( 'tfx_last_update', strtotime('now') );
}

add_filter('site_transient_update_plugins', 'tfx_update');

function tfx_update( $transient ){
	if ( empty($transient->checked ) )
		return $transient;

	if( false == $remote = get_transient( 'upgrade_tables-from-xml' ) ) {

		// info.json is the file with the actual plugin information on your server
		$remote = wp_remote_get( 'https://patryksawicki.pl/wp-plugins/tables-from-xml/info.json', array(
			                                                                   'timeout' => 10,
			                                                                   'headers' => array(
				                                                                   'Accept' => 'application/json'
			                                                                   ) )
		);

		if ( !is_wp_error( $remote ) && isset( $remote['response']['code'] ) && $remote['response']['code'] == 200 && !empty( $remote['body'] ) ) {
			set_transient( 'misha_upgrade_YOUR_PLUGIN_SLUG', $remote, 43200 ); // 12 hours cache
		}

	}

	if( $remote ) {

		$remote = json_decode( $remote['body'] );
		$pluginData=get_plugin_data( __FILE__ );

		// your installed plugin version should be on the line below! You can obtain it dynamically of course
		if( $remote && version_compare( $pluginData['Version'], $remote->version, '<' ) && version_compare($remote->requires, get_bloginfo('version'), '<' ) ) {
			$res = new stdClass();
			$res->slug = 'tables-from-xml';
			$res->plugin = 'tables-from-xml/tables-from-xml.php'; // it could be just YOUR_PLUGIN_SLUG.php if your plugin doesn't have its own directory
			$res->new_version = $remote->version;
			$res->tested = $remote->tested;
			$res->package = $remote->download_url;
			$transient->response[$res->plugin] = $res;
			//$transient->checked[$res->plugin] = $remote->version;
		}

	}
	return $transient;
}

add_action( 'upgrader_process_complete', 'tfx_after_update', 10, 2 );

function tfx_after_update( $upgrader_object, $options ) {
	if ( $options['action'] == 'update' && $options['type'] === 'plugin' )  {
		// just clean the cache when new plugin version is installed
		delete_transient( 'upgrade_tables-from-xml' );
	}
}