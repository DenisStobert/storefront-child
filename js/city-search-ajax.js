jQuery(document).ready(function($) {
    // Function for city search that triggers when a key is pressed in the input field
    $('#city-search').on('keyup', function() {
        // Get the current search query from the input field
        var searchQuery = $(this).val();

        // If the search query is empty, clear the table
        if (searchQuery === '') {
            $('#countries-cities-table-container').html(''); // Clear the table
            return; // Exit the function
        }

        // Send an AJAX request to fetch cities based on the search query
        $.ajax({
            url: citySearchAjax.ajax_url, // The AJAX URL set in WordPress
            method: 'POST', // The request method
            data: {
                action: 'city_search', // The action hook defined in WordPress
                search: searchQuery // The search query entered by the user
            },
            success: function(response) {
                if (response.success) {
                    // If the AJAX request is successful, update the table with results
                    var citiesData = response.data; // Get the list of cities from the response
                    var tableHtml = '<table><tr><th>Country</th><th>City</th><th>Temperature</th></tr>';

                    // Array to hold promises for weather data requests
                    var weatherPromises = [];

                    // Loop through the cities data
                    citiesData.forEach(function(city) {
                        var latitude = city.latitude; // Get the latitude of the city
                        var longitude = city.longitude; // Get the longitude of the city
                        var apiKey = '08dee94192fe52f88bf26c96272cb7d8'; // Your OpenWeatherMap API key

                        // Make a request to the OpenWeatherMap API to fetch the current temperature for the city
                        var weatherRequest = $.get("http://api.openweathermap.org/data/2.5/weather?lat=" + latitude + "&lon=" + longitude + "&appid=" + apiKey + "&units=metric")
                            .then(function(weatherData) {
                                // If the weather data is available, extract the temperature
                                var temperature = (weatherData.main && weatherData.main.temp) ? weatherData.main.temp : 'N/A';
                                // Add a new row to the table with the city details and temperature
                                tableHtml += '<tr><td>' + city.country + '</td><td>' + city.city + '</td><td>' + temperature + ' °C</td></tr>';
                            });

                        // Add the weather data request promise to the array
                        weatherPromises.push(weatherRequest);
                    });

                    // Once all weather data has been fetched, update the table with the complete data
                    $.when.apply($, weatherPromises).done(function() {
                        tableHtml += '</table>';
                        // Update the container with the newly created table
                        $('#countries-cities-table-container').html(tableHtml);
                    });
                }
            }
        });
    });
});
