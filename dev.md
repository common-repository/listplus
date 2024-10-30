GET fontawesome 5 from JSon file.
```php
$icon_json_path = LISTPLUS_PATH . '/assets/fontawesome/metadata/icons.json';
$icons = [];
if ( \file_exists( $icon_json_path ) ) {
	$content = \file_get_contents( $icon_json_path );
	$contents = \json_decode( $content, true );
	foreach ( (array) $contents as $k => $args ) {
		foreach ( $args['styles'] as $sk ) {
			$prefix = \substr( $sk, 0, 1 );
			$svg = $args['svg'][ $sk ]['raw'];
			// echo '<span class="icon">'.$svg.'</span>';
			$id = 'fa' . $prefix . ' ' . $k;
			$icons[ 'fa' . $prefix . ' ' . $k ] = [
				'id' => $id,
				'text' => $k,
				'svg' => $svg,
			];
		}
	}
}

$icon_path = LISTPLUS_PATH . '/inc/fontawesome5.php';
\file_put_contents( $icon_path, "<?php\n" . var_export( $icons, true ) . ";\n" );

```