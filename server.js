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
app.use(express.json({limit: '10mb'})); // Increase JSON size limit

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
      hasApiKey: !!apiKey,
      apiKeyFirstChars: apiKey ? `${apiKey.substring(0, 5)}...${apiKey.substring(apiKey.length - 4)}` : 'none' 
    });
    
    if (!apiKey) {
      console.log('API key missing');
      return res.status(400).json({ error: 'API key is required' });
    }

    console.log('Making request to Claude API...');
    console.log('Authorization header:', `Bearer ${apiKey.substring(0, 5)}...`);
    console.log('API version header:', '2023-06-01');
    
    const response = await axios.post('https://api.anthropic.com/v1/messages', {
      model: model || 'claude-3-7-sonnet-20250219',
      max_tokens: 1000,
      messages: [
        { role: 'user', content: prompt }
      ]
    }, {
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${apiKey}`,  // Using Bearer token format
        'anthropic-version': '2023-06-01',
        // Try a newer API version as fallback if the client isn't already using one
        'x-anthropic-version': '2023-06-01',
        'x-api-key': apiKey  // Adding this header as an alternative authentication method
      },
      timeout: 30000 // 30 second timeout
    });

    console.log('Received response from Claude API:', {
      status: response.status,
      dataReceived: !!response.data
    });
    
    res.json(response.data);
  } catch (error) {
    console.error('Error proxying to Claude API:', error.response?.data || error.message);
    
    // Log more details about the error
    if (error.response) {
      console.log('Error response status:', error.response.status);
      console.log('Error response headers:', error.response.headers);
      console.log('Error details:', JSON.stringify(error.response.data, null, 2));
    } else {
      console.log('Error details:', error.message);
    }
    
    // Forward Claude API error response if available
    if (error.response?.data) {
      console.log('Forwarding Claude API error response');
      return res.status(error.response.status).json(error.response.data);
    }
    
    // Handle timeout errors specifically
    if (error.code === 'ECONNABORTED') {
      console.log('Request timed out');
      return res.status(504).json({ error: 'Request to Claude API timed out' });
    }
    
    console.log('Sending generic error response');
    res.status(500).json({ error: `Failed to call Claude API: ${error.message}` });
  }
});

// Start the server
app.listen(PORT, () => {
  const isDev = process.env.NODE_ENV === 'development';
  console.log(`Proxy server running on port ${PORT}${isDev ? ' (development mode with hot-reload)' : ''}`);
  console.log(`Access the translation chat at http://localhost:${PORT}`);
});