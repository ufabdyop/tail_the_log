<?
	require ('JSON.php');
	require ('config.php');
	$offset = 0;
	$filename = $config['default_file'] ;
	$allowed_files = $config['allowed_files'] ;

	function current_url() {
		return (!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] : "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
	}
	function initialize() {
		global $offset , $filename ;
		if (isset($_GET['filename'])) { 
			$filename = $_GET['filename'];
		} 
		if (isset($_GET['offset'])) { 
			$offset = $_GET['offset'];
		} 
	}
	function readlines() {
		global $offset , $filename, $allowed_files ;
		if (!in_array( $filename, $allowed_files )) {
			$the_data = "$filename not in allowed files: " . implode(',', $allowed_files);
			if ($offset) {
				return array('offset' => strlen($the_data), 'the_data' => '');
			}
			return array('offset' => strlen($the_data), 'the_data' => ($the_data));
		}
		$fh = fopen($filename, 'r');
		$the_data = '';
		if ($fh) {
			if ($offset) {
				//figure out if there is more data to read
				$chars_to_read =  (filesize($filename) - $offset) ;
				$seek_to = $offset - 1;
				if ($chars_to_read > 0) {
					fseek($fh, $seek_to );
					$read_chars = fread($fh, $chars_to_read);
					$the_data = $read_chars;
					//$offset = ftell($fh);
					$offset += $chars_to_read;
				}
			} else {
				$size = filesize($filename) ;
				//just read some number of characters from end of file
				if ($size <= 4096) {
					$the_data = file_get_contents($filename);
					$offset = $size;
				} else {
					//start 4096 characters from the end
					$fseek_success = fseek($fh, -4096, SEEK_END);
					$num_chars_read = 0;
					while ($char = fread($fh, 1)) {
						$num_chars_read++;
						if ($char == "\n") {
							break;
						}
					}
					$the_data = fread($fh, 4096 - $num_chars_read);
					$offset = ftell($fh);
				}
			}
			fclose($fh);
			return array('offset' => $offset, 'the_data' => ($the_data));
		}
	}
	initialize();
	if (isset($_GET['justread'])) {
		$json = new Services_JSON();
		echo $json->encode(readlines());
		die;
	}
?>
<html>
<head>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
<style>
	#the_log {
		width: 100%;
		height: 98%;
		font-size: 10px;
		overflow: scroll;
	}
</style>

</head>
<body>
<pre id="the_log">
</pre>
<script>
var offset = <?=$offset?>;
var init = true;
function load_latest() {
	$.ajax({
			url: '',
			dataType: 'json',
			data: {
				offset: offset,
				justread: true,
				filename: '<?=$filename?>'
				},
			success: function (data) {
					offset = data['offset'];
					$('#the_log').html($('#the_log').html() + data['the_data']);
					if (data['the_data']) {
						//scroll to the bottom
						$("#the_log").prop({ scrollTop: $("#the_log").prop("scrollHeight") });
					}
					setTimeout(load_latest, 3000);
				}
			});

}
$(document).ready(function() {
		load_latest();
		});
</script>
</body>
</html>
