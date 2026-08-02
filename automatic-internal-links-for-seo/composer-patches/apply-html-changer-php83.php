<?php
/**
 * Apply the audited Pagup PHP compatibility patch to html-changer 0.1.6.
 *
 * Composer runs this file after installing or updating dependencies. The
 * patcher fails closed when the upstream bytes are not the audited version.
 *
 * @package Pagup_Auto_Links
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

$composer_root = dirname( __DIR__ );
$vendor_root   = $composer_root . '/vendor/friedolinfoerder/html-changer/src';
$patches       = array(
	array(
		'path'          => $vendor_root . '/EndingTag.php',
		'original_hash' => 'efaa6ab06401873e8704077d706a0f57385fb77c26dedcdb7983f109c1f1b487',
		'patched_hash'  => '8a87b84f2255023438d8c030087338b119f37475df40a422d6c1ed780baef689',
		'replacements'  => array(
			array(
				'search'  => "    public \$parent = null;\n\n    public function getType()",
				'replace' => "    public \$parent = null;\n    public \$attributes = array();\n\n    public function getType()",
			),
			array(
				'search'  => "\n}",
				'replace' => "\n}\n",
			),
		),
	),
	array(
		'path'          => $vendor_root . '/HtmlChanger.php',
		'original_hash' => '2defac7f1c56a3242dc490309896cc31b11fd47b2daddb84e97aecc0642f9fd3',
		'patched_hash'  => '4824a6b768ebf711d85b6c3134aec53cb5104e00c625fbc778d5e968384f9219',
		'replacements'  => array(
			array(
				'search'  => '                    $followingChar = mb_strtolower($this->getChar(1));',
				'replace' => '                    $followingChar = mb_strtolower((string) $this->getChar(1));',
			),
			array(
				'search'  => '    public function parts($onlyText = false, array $excludeElements = null)',
				'replace' => '    public function parts($onlyText = false, ?array $excludeElements = null)',
			),
		),
	),
);
$updates       = array();

foreach ( $patches as $patch ) {
	if ( ! is_file( $patch['path'] ) ) {
		fwrite( STDERR, 'Pagup patch target is missing: ' . $patch['path'] . PHP_EOL );
		exit( 1 );
	}

	$contents = file_get_contents( $patch['path'] );
	if ( false === $contents ) {
		fwrite( STDERR, 'Pagup patch target cannot be read: ' . $patch['path'] . PHP_EOL );
		exit( 1 );
	}

	$current_hash = hash( 'sha256', $contents );
	if ( $current_hash === $patch['patched_hash'] ) {
		continue;
	}

	if ( $current_hash !== $patch['original_hash'] ) {
		fwrite( STDERR, 'Pagup patch refused unknown upstream bytes: ' . $patch['path'] . PHP_EOL );
		exit( 1 );
	}

	$patched_contents = $contents;
	foreach ( $patch['replacements'] as $replacement ) {
		if ( 1 !== substr_count( $patched_contents, $replacement['search'] ) ) {
			fwrite( STDERR, 'Pagup patch expected one exact replacement in: ' . $patch['path'] . PHP_EOL );
			exit( 1 );
		}

		$patched_contents = str_replace( $replacement['search'], $replacement['replace'], $patched_contents );
	}

	if ( hash( 'sha256', $patched_contents ) !== $patch['patched_hash'] ) {
		fwrite( STDERR, 'Pagup patch produced unexpected bytes for: ' . $patch['path'] . PHP_EOL );
		exit( 1 );
	}

	$updates[] = array(
		'path'              => $patch['path'],
		'original_contents' => $contents,
		'patched_contents'  => $patched_contents,
		'patched_hash'      => $patch['patched_hash'],
	);
}

$written_updates = array();

foreach ( $updates as $update ) {
	$bytes_written = file_put_contents( $update['path'], $update['patched_contents'], LOCK_EX );
	$write_valid   = strlen( $update['patched_contents'] ) === $bytes_written
		&& hash_file( 'sha256', $update['path'] ) === $update['patched_hash'];

	if ( ! $write_valid ) {
		$rollback_failed  = false;
		$rollback_updates = array_merge( $written_updates, array( $update ) );
		foreach ( $rollback_updates as $written_update ) {
			$restored_bytes  = file_put_contents( $written_update['path'], $written_update['original_contents'], LOCK_EX );
			$rollback_failed = $rollback_failed || strlen( $written_update['original_contents'] ) !== $restored_bytes;
		}

		$message = 'Pagup patch could not write verified bytes: ' . $update['path'];
		if ( $rollback_failed ) {
			$message .= ' (rollback also failed)';
		}

		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}

	$written_updates[] = $update;
}

fwrite( STDOUT, "Pagup html-changer PHP compatibility patch verified.\n" );
