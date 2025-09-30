import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap'
import router from './router'
import './assets/openpage.css'
import axios from 'axios'

import { createApp } from 'vue'
import App from './App.vue'

createApp(App)
    .use(router)
    .use(axios)
    .mount('#app')
