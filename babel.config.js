/**
 * Babel configuration for LogMate plugin.
 *
 * @package LogMate
 */

module.exports = function ( api ) {
	api.cache( process.env.NODE_ENV === 'production' );

	const isDevelopment = process.env.NODE_ENV === 'development';

	return {
		presets: [
			['@babel/preset-env', {
				targets: {
					browsers: ['> 1%', 'last 2 versions']
				}
		}],
			['@babel/preset-react', {
				runtime: 'automatic',
				development: isDevelopment
		}],
			'@babel/preset-typescript'
		]
	};
};
