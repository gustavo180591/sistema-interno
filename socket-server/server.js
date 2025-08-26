const http = require('http');
const { Server } = require('socket.io');

// Create HTTP server
const server = http.createServer();
const io = new Server(server, {
    cors: {
        origin: ["http://localhost:8000", "https://localhost:8000"],
        methods: ["GET", "POST", "OPTIONS"],
        allowedHeaders: ["Content-Type", "Authorization"],
        credentials: true
    },
    pingTimeout: 60000,
    pingInterval: 25000
});

// Store active rooms
const activeRooms = new Set();

io.on('connection', (socket) => {
    console.log('New client connected:', socket.id);

    // Join a ticket room
    socket.on('joinTicket', (data) => {
        const roomId = `ticket_${data.ticketId}`;
        socket.join(roomId);
        activeRooms.add(roomId);
        console.log(`User ${socket.id} joined room ${roomId}`);
    });

    // Handle new note
    socket.on('newNote', (data) => {
        const roomId = `ticket_${data.ticketId}`;
        console.log(`New note in room ${roomId} from ${data.userName}`);
        
        // Broadcast to all clients in the room except the sender
        socket.to(roomId).emit('newNote', {
            ...data,
            timestamp: new Date().toISOString()
        });
    });

    // Handle disconnection
    socket.on('disconnect', () => {
        console.log('Client disconnected:', socket.id);
    });

    // Error handling
    socket.on('error', (error) => {
        console.error('Socket error:', error);
    });
});

// Start the server
const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
    console.log(`Socket.IO server running on port ${PORT}`);
});

// Handle server errors
server.on('error', (error) => {
    console.error('Server error:', error);
});

// Handle uncaught exceptions
process.on('uncaughtException', (error) => {
    console.error('Uncaught exception:', error);
});

// Handle unhandled promise rejections
process.on('unhandledRejection', (reason, promise) => {
    console.error('Unhandled rejection at:', promise, 'reason:', reason);
});
