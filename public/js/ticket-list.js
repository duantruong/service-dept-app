// Handle clickable ticket boxes
document.addEventListener('DOMContentLoaded', function() {
    const ticketBoxes = document.querySelectorAll('.clickable-ticket-box');
    const ticketModal = new bootstrap.Modal(document.getElementById('ticketModal'));
    const ticketModalLabel = document.getElementById('ticketModalLabel');
    const ticketListItems = document.getElementById('ticketListItems');
    const ticketListLoading = document.getElementById('ticketListLoading');
    const ticketListContent = document.getElementById('ticketListContent');
    const ticketListEmpty = document.getElementById('ticketListEmpty');

    ticketBoxes.forEach(box => {
        box.addEventListener('click', function() {
            const type = this.dataset.type;
            const typeLabels = {
                'created': 'Tickets Created',
                'resolved': 'Tickets Resolved',
                'remaining': 'Remaining Tickets'
            };

            ticketModalLabel.textContent = typeLabels[type] || 'Ticket List';
            ticketListItems.innerHTML = '';
            ticketListLoading.style.display = 'block';
            ticketListContent.style.display = 'none';
            ticketListEmpty.style.display = 'none';

            ticketModal.show();

            // Fetch tickets
            fetchTickets(type);
        });
    });

    function fetchTickets(type) {
        const isFiltered = window.isFiltered || false;
        const weekIndex = window.weekIndex || 0;
        const weekTickets = window.weekTickets || {};

        // If we have week tickets data and not filtered, use it directly
        if (!isFiltered && weekTickets[type]) {
            displayTickets(weekTickets[type]);
            return;
        }

        // Otherwise fetch from server
        const baseUrl = window.location.origin + '/tickets/list';
        const url = new URL(baseUrl);
        url.searchParams.append('type', type);
        url.searchParams.append('week', weekIndex);
        url.searchParams.append('filtered', isFiltered ? '1' : '0');
        
        if (!isFiltered && weekTickets) {
            url.searchParams.append('weekTickets', JSON.stringify(weekTickets));
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                displayTickets(data.tickets || []);
            })
            .catch(error => {
                console.error('Error fetching tickets:', error);
                ticketListLoading.style.display = 'none';
                ticketListEmpty.textContent = 'Error loading tickets. Please try again.';
                ticketListEmpty.style.display = 'block';
            });
    }

    function displayTickets(tickets) {
        ticketListLoading.style.display = 'none';

        if (tickets.length === 0) {
            ticketListEmpty.style.display = 'block';
            ticketListContent.style.display = 'none';
            return;
        }

        ticketListEmpty.style.display = 'none';
        ticketListContent.style.display = 'block';
        ticketListItems.innerHTML = '';

        tickets.forEach(ticket => {
            const li = document.createElement('li');
            li.className = 'list-group-item';
            li.textContent = ticket;
            ticketListItems.appendChild(li);
        });
    }
});

