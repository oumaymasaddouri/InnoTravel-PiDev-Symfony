$(document).ready(function () {
    // Delete Itinéraire
    $('.delete-itineraire').on('click', function () {
        let itineraireId = $(this).data('id');
        Swal.fire({
            title: "Are you sure?",
            text: "This action cannot be undone!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/delete-itineraire/${itineraireId}`,
                    type: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function () {
                        Swal.fire("Deleted!", "Itinéraire has been deleted.", "success")
                            .then(() => location.reload());
                    },
                    error: function () {
                        Swal.fire("Error!", "Could not delete itinéraire.", "error");
                    }
                });
            }
        });
    });

    // Delete Trip
    $('.delete-trip').on('click', function () {
        let tripId = $(this).data('id');
        Swal.fire({
            title: "Are you sure?",
            text: "This action cannot be undone!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/delete-trip/${tripId}`,
                    type: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function () {
                        Swal.fire("Deleted!", "Trip has been deleted.", "success")
                            .then(() => location.reload());
                    },
                    error: function () {
                        Swal.fire("Error!", "Could not delete Trip.", "error");
                    }
                });
            }
        });
    });

    if (window.location.pathname === '/admin/create-trip') {
        let getItineraireListUrl = Routing.generate('get_itineraire_list');

        $.getJSON(getItineraireListUrl, function (data) {
            let $select = $('#itineraireSelect');

            $.each(data, function (index, itineraire) {
                $select.append($('<option>', {
                    value: itineraire.id,
                    text: itineraire.name
                }));
            });

            $('.selectpicker').selectpicker('refresh');
        }).fail(function () {
            console.error('Error fetching itineraries');
        });
    }
    

    fetchTripData();
    function fetchTripData() {
        $.ajax({
            url: Routing.generate('trip_status_count'), // Use FOSJsRouting to generate the URL
            method: 'GET',
            success: function(data) {
                // Extracting the data
                let statusCounts = data.statusCounts;
                let totalTrips = data.totalTrips;
                
                // Update total trips on the page
                $('#totalTrips').text(totalTrips);
    
                // Prepare data for the chart
                let statuses = [];
                let counts = [];
                
                // Fill statuses and counts arrays
                statusCounts.forEach(function(statusCount) {
                    statuses.push(statusCount.status);
                    counts.push(statusCount.count);
                });
    
                // Render the chart
                renderChart(statuses, counts);
            },
            error: function(error) {
                console.error("There was an error fetching the trip data:", error);
            }
        });
    }
    
    // Function to render the chart
    function renderChart(statuses, counts) {
        var ctx = document.getElementById('statusChart').getContext('2d');
        var chart = new Chart(ctx, {
            type: 'bar', // or 'pie', 'line', etc.
            data: {
                labels: statuses,
                datasets: [{
                    label: 'Trips by Status',
                    data: counts,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});