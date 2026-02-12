import axios, { AxiosInstance, InternalAxiosRequestConfig } from 'axios';

// Declare global LogMateData object.
declare const LogMateData: { nonce: string; restUrl: string };

// Create axios instance.
const api: AxiosInstance = axios.create( {
	baseURL: LogMateData.restUrl,
} );

// Request interceptor to add nonce.
api.interceptors.request.use(
	( config: InternalAxiosRequestConfig ): InternalAxiosRequestConfig => {
		config.headers[ 'X-WP-Nonce' ] = LogMateData.nonce;
		return config;
	},
	( error ) => Promise.reject( error )
);

export default api;

