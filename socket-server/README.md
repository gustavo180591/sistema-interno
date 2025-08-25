# Socket.IO Server for Real-time Ticket Notes

This is a Socket.IO server that enables real-time updates for ticket notes in the Sistema Interno application.

## Prerequisites

- Node.js (v14 or later)
- npm (comes with Node.js)

## Installation

1. Navigate to the socket-server directory:
   ```bash
   cd socket-server
   ```

2. Install dependencies:
   ```bash
   npm install
   ```

## Configuration

By default, the server runs on port 3000. To change this, modify the `PORT` environment variable in the `.env` file or set it when starting the server:

```bash
PORT=3001 node server.js
```

## Running the Server

### Development Mode
```bash
npm run dev
```

### Production Mode
```bash
npm start
```

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| PORT     | 3000    | Port number for the Socket.IO server |
| NODE_ENV | development | Environment mode (development/production) |

## API Endpoints

- `POST /` - WebSocket connection endpoint

## Socket Events

### Client to Server
- `joinTicket` - Join a ticket room
  ```javascript
  {
    ticketId: string  // ID of the ticket to join
  }
  ```

- `newNote` - Send a new note
  ```javascript
  {
    ticketId: string,  // ID of the ticket
    noteId: string,    // ID of the note
    content: string,   // Note content
    userName: string,  // Name of the user who created the note
    isAdmin: boolean,  // Whether the user is an admin
    isOwner: boolean   // Whether the user is the note owner
  }
  ```

### Server to Client
- `newNote` - Receive a new note
  ```javascript
  {
    ticketId: string,  // ID of the ticket
    noteId: string,    // ID of the note
    content: string,   // Note content
    userName: string,  // Name of the user who created the note
    isAdmin: boolean,  // Whether the user is an admin
    isOwner: boolean,  // Whether the user is the note owner
    timestamp: string  // ISO timestamp of when the note was created
  }
  ```

## Security

- CORS is configured to only allow requests from `http://localhost:8000`
- Always run this behind a reverse proxy (like Nginx) in production
- Use HTTPS in production

## Deployment

For production deployment, it's recommended to use a process manager like PM2:

```bash
npm install -g pm2
pm2 start server.js --name "ticket-notes-socket"
```

## License

This project is part of the Sistema Interno application.
