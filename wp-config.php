<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'cursonalta' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'p$i9|C_rd^:VP7P$<SIQg>eO;bX/q=CF#zb1F]QQQB&ko8:/!8!^ESGrAe0RO?>(' );
define( 'SECURE_AUTH_KEY',  'Yg6+){/2h3rEMwX;m?;~chy5:O#NcyWpGN@XDa8x]Q.Qq7(!5uM,w?hFC:z(=8!g' );
define( 'LOGGED_IN_KEY',    'oP.8[6RLr@;Ap38^A:KudLPci L^a[(GcM}%C^nqT,$aoz;Or%,gu;m$Wp p t++' );
define( 'NONCE_KEY',        'Pg`Idp.+BRI<]18>ID@_3v@! -GQv.B<U}Q|:>O*~ydY|9vzLGtm5Mo=[t2/h}6_' );
define( 'AUTH_SALT',        's_~5Fl(UG3u.TuRj)6?!yM]Z+pRcTcdJ#C},zaWwGg(!<*ih%D_ai`ZEk;-R?B=h' );
define( 'SECURE_AUTH_SALT', '4iww8a]hKx8zC=hu`:8,o^t:BP-k.rLTFB(GI}-eLplO|@.s;;dJg+*+@vso QN?' );
define( 'LOGGED_IN_SALT',   'UrEc~XSh1-Jt-=*6E%{?=#4M-?NZ(p-Dkll6B PQ`HCVas{Hw7#|TTvMR4mQG/VH' );
define( 'NONCE_SALT',       'PBqF?7@i Gg|/]brlHvxO~c0eZ/=K@Nizc?,.|/zJB3Pxo4Ei4E[akP^kd$ij/92' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
