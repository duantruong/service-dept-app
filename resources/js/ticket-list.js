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
            const tickets = Array.isArray(weekTickets[type]) ? weekTickets[type] : [];
            displayTickets(tickets);
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

        // Get CSRF token from meta tag
        const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content;

        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrfToken && { 'X-CSRF-TOKEN': csrfToken })
            },
            credentials: 'same-origin'
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
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
            const tr = document.createElement('tr');
            
            // Handle both old format (string) and new format (object with ticket and sn)
            let ticketNum = '';
            let ticketSn = '';
            
            if (typeof ticket === 'string') {
                // Old format: just a string
                ticketNum = ticket;
            } else if (ticket && typeof ticket === 'object') {
                // New format: object with details
                ticketNum = ticket.ticket || '';
                ticketSn = ticket.sn || '';
            }
            
            // Create table cells
            const tdTicket = document.createElement('td');
            tdTicket.textContent = ticketNum;
            tr.appendChild(tdTicket);
            
            const tdSn = document.createElement('td');
            tdSn.textContent = ticketSn;
            tr.appendChild(tdSn);
            
            ticketListItems.appendChild(tr);
        });
    }
});
