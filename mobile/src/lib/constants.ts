// IP LAN de la machine de développement
// Modifiable via la variable d'environnement EXPO_PUBLIC_API_HOST
const API_HOST = process.env.EXPO_PUBLIC_API_HOST ?? '192.168.1.43';

export const API_BASE_URL = `http://${API_HOST}:8000/api`;
export const REVERB_HOST  = API_HOST;
export const REVERB_PORT  = 8080;
export const REVERB_APP_KEY = 'quiz-app-key';
