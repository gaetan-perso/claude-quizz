// Hôte ou URL complète de l'API.
// Exemples valides pour EXPO_PUBLIC_API_HOST :
//   192.168.1.43              → http://192.168.1.43:8000/api
//   https://monapi.com        → https://monapi.com/api  (port ignoré)
//   http://monapi.com:9000    → http://monapi.com:9000/api
const RAW_HOST = process.env.EXPO_PUBLIC_API_HOST ?? '192.168.1.43';

const isFullUrl = RAW_HOST.startsWith('http://') || RAW_HOST.startsWith('https://');

export const API_BASE_URL = isFullUrl
  ? `${RAW_HOST.replace(/\/$/, '')}/api`
  : `http://${RAW_HOST}:8000/api`;

// Pour Reverb : extraire juste le hostname sans protocole ni port
export const REVERB_HOST = isFullUrl
  ? new URL(RAW_HOST).hostname
  : RAW_HOST;

export const REVERB_PORT  = isFullUrl ? (new URL(RAW_HOST).port ? Number(new URL(RAW_HOST).port) : 443) : 8080;
export const REVERB_APP_KEY = 'quiz-app-key';
