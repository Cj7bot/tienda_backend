/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// Import Bootstrap CSS and JS
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';

// start the Stimulus application
import './bootstrap';

// Import Chart.js
import { Chart } from 'chart.js/auto';

// Configure Chart.js defaults
Chart.defaults.color = '#666';
Chart.defaults.font.family = "'Helvetica Neue', 'Helvetica', 'Arial', sans-serif";
Chart.defaults.responsive = true;
Chart.defaults.maintainAspectRatio = false;

// Import dashboard functionality
import './admin/dashboard.js';
