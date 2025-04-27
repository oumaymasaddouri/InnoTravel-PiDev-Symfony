$(document).ready(function () {
    if (window.location.pathname === '/user/create-trip') {
    const $select = $('#itineraireSelect');

    // Ensure selectpicker is loaded
    if (typeof $select.selectpicker !== 'function') {
        console.error('Bootstrap Select not loaded. Check your imports.');
        return;
    }

    $select.selectpicker(); // Initialize select picker

    // Fetch itineraries via AJAX
    $.ajax({
        url: Routing.generate('user_get_itineraire_list'),
        method: 'GET',
        success: function (data) {
            $select.empty(); // Clear existing options

            data.forEach(item => {
                const optionText = `${item.name} - ${item.dayProgram} - ${item.activity}`;
                $select.append(new Option(optionText, item.id));
            });

            $select.selectpicker('refresh'); // Refresh Bootstrap Select UI
        },
        error: function () {
            console.error('Failed to load itineraries.');
        }
    });

    }
    $('.nice-select.form-control.selectpicker').hide();
});

$(document).ready(function () {
    $('.delete-trip-btn').on('click', function (e) {
        e.preventDefault();
        
        let tripId = $(this).data('id'); // Get trip ID from button data attribute
        let deleteUrl = Routing.generate('user_delete_trip', { id: tripId }); // Generate the Symfony route
        
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: deleteUrl,
                    type: "POST", // Make sure to use POST as the route expects POST
                    success: function (response) {
                        Swal.fire("Deleted!", "Trip has been deleted.", "success");
                        setTimeout(function () {
                            location.reload(); // Refresh the page to update the list
                        }, 1000);
                    },
                    error: function (xhr) {
                        Swal.fire("Error!", "Failed to delete trip.", "error");
                    }
                });
            }
        });
    });
});

$(document).ready(function() {
    $('#chat-form').on('submit', function(e) {
        e.preventDefault();

        let userMessage = $('#user-message').val();
        if (userMessage.trim() === '') return;

        $('#chat-box').append('<div><strong>You:</strong> ' + $('<div>').text(userMessage).html() + '</div>');
        $('#user-message').val('');
        $('#chat-box').scrollTop($('#chat-box')[0].scrollHeight);

        $.ajax({
            url: Routing.generate('api_travel_chat'),
            method: 'GET',
            data: { message: userMessage },
            success: function(response) {

                $('#chat-box').append('<div class="text-primary"><strong>InnoTravel AI Assistant:</strong> ' + $('<div>').text(response.response).html() + '</div>');
                $('#chat-box').scrollTop($('#chat-box')[0].scrollHeight);
            },
            error: function(xhr) {
                $('#chat-box').append('<div class="text-danger"><strong>Error:</strong> ' + (xhr.responseJSON?.error ?? 'Unknown error') + '</div>');
                $('#chat-box').scrollTop($('#chat-box')[0].scrollHeight);
            }
        });
    });
});
