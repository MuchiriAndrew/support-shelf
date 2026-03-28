import axios from 'axios';
import Alpine from 'alpinejs';

import './assistant-shell';
import './echo';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['X-CSRF-TOKEN'] = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute('content');
window.Alpine = Alpine;

Alpine.start();
