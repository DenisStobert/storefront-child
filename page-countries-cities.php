<?php
/* Template Name: Countries and Cities */

// Include the header for the WordPress theme
get_header(); ?>

<div class="countries-cities-container">
    <!-- Title for the Countries and Cities page -->
    <h1>Countries and Cities</h1>

    <!-- Search form for the city search functionality -->
    <div class="city-search-container">
        <!-- Input field for entering the city name, with a placeholder text -->
        <input type="text" id="city-search" placeholder="Search for a city...">
    </div>

    <!-- Container where the countries and cities table will be displayed -->
    <div id="countries-cities-table-container">
        <!-- The table will be loaded here dynamically via AJAX -->
    </div>

</div>

<?php
// Include the footer for the WordPress theme
get_footer();
