/**
 * Webpack configuration for LogMate plugin.
 *
 * @package LogMate
 */

const path = require( 'path' );
const isProduction = process.env.NODE_ENV === 'production';
const WebpackBar = ! isProduction ? require( "webpackbar" ) : null;
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const webpack = require( 'webpack' );

module.exports = {
	entry: './src/index.tsx',
	devServer: {
		headers: {'Access-Control-Allow-Origin': '*'},
		allowedHosts: 'all',
		host: 'localhost',
		port: 5433,
		hot: true, // Enable Hot Module Replacement.
		liveReload: false, // Disable live reload, use HMR instead.
		client: {
			overlay: false,
			webSocketURL: 'ws://localhost:5433/ws',
			// Prevent fallback to live reload when HMR fails.
			webSocketTransport: 'ws',
		},
		webSocketServer: {
			type: 'ws',
			options: {
				port: 5433,
			},
		},
	},
	output: {
		path: path.resolve( __dirname, 'assets/build' ),
		filename: 'admin.js',
		// Use relative path in production so chunks load from same directory as admin.js.
		// In development, use dev server URL.
		publicPath: isProduction ? '' : 'http://localhost:5433/',
		clean: true, // Clean the output directory before each build.
	},
	mode: isProduction ? 'production' : 'development',
	devtool: isProduction ? false : 'source-map',
	resolve: {
		extensions: ['.tsx', '.ts', '.js', '.jsx'],
	},
	module: {
		rules: [
			{
				test: /\.(ts|tsx|js|jsx)$/,
				exclude: /node_modules/,
				use: {
					loader: 'babel-loader',
					options: {
						cacheDirectory: isProduction,
						configFile: path.resolve( __dirname, 'babel.config.js' ),
					},
				},
		},
			{
				test: /\.css$/i,
				use: [
					isProduction ? MiniCssExtractPlugin.loader : 'style-loader',
					'css-loader',
					'postcss-loader'
				]
		},
		],
	},
	plugins: [
		...(isProduction ? [new MiniCssExtractPlugin(
			{
				filename: '../css/admin.css', // Will generate in /assets/css/.
			}
		)] : []),
		...( ! isProduction && WebpackBar ? [new WebpackBar()] : []),
		// Enable HMR in development mode.
		...( ! isProduction ? [
			new webpack.HotModuleReplacementPlugin(),
		] : [])
	],
};
