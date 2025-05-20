// server.js - Node.js proxy server for Claude API
const express = require('express');
const cors = require('cors');
const axios = require('axios');
const app = express();
const PORT = process.env.PORT || 3000;

// Enable CORS for browser requests with specific configuration
app.use(cors({
  origin: '*', // Allow requests from any origin
  methods: ['GET', 'POST', 'OPTIONS'],
  allowedHeaders: ['Content-Type', 'Authorization'],
  credentials: true
}));
app.use(express.json());

// Log all incoming requests to help with debugging
app.use((req, res, next) => {
  console.log(`${new Date().toISOString()} - ${req.method} ${req.url}`);
  next();
});

// Serve static files from 'public' directory
app.use(express.static('public'));

// Simple test endpoint to verify server is working
app.get('/api/test', (req, res) => {
  console.log('Test endpoint called');
  res.json({ status: 'ok', message: 'Server is running correctly' });
});

// Proxy endpoint for Claude API
app.post('/api/claude', async (req, res) => {
  try {
    console.log('Received request to /api/claude');
    const { apiKey, model, prompt } = req.body;
    
    console.log('Request body received:', { 
      model, 
      promptLength: prompt?.length || 0,
      hasApiKey: !!apiKey 
    });
    
    if (!apiKey) {
      console.log('API key missing');
      return res.status(400).json({ error: 'API key is required' });
    }

    console.log('Making request to Claude API...');
    const response = await axios.post('https://api.anthropic.com/v1/messages', {
      model: model || 'claude-3-7-sonnet-20250219',
      max_tokens: 1000,
      messages: [
        { role: 'user', content: prompt }
      ]
    }, {
      headers: {
        'Content-Type': 'application/json',
        'x-api-key': apiKey,
        'anthropic-version': '2023-06-01'
      }
    });

    console.log('Received response from Claude API:', {
      status: response.status,
      dataReceived: !!response.data
    });
    
    res.json(response.data);
  } catch (error) {
    console.error('Error proxying to Claude API:', error.response?.data || error.message);
    console.error('Full error:', error);
    
    // Forward Claude API error response if available
    if (error.response?.data) {
      console.log('Forwarding Claude API error response');
      return res.status(error.response.status).json(error.response.data);
    }
    
    console.log('Sending generic error response');
    res.status(500).json({ error: `Failed to call Claude API: ${error.message}` });
  }
});

// Start the server
app.listen(PORT, () => {
  console.log(`Proxy server running on port ${PORT}`);
  console.log(`Access the translation chat at http://localhost:${PORT}`);
});