# Translation Chat Interface

A multilingual chat interface that facilitates communication between a customer speaking a foreign language and a receiver using a base language, with Claude AI acting as a translator.

## Features

- **Multilingual Support**: Translates between multiple languages
- **Device Detection**: Automatically sets the appropriate mode based on screen size
  - Mobile devices -> Customer mode (foreign language)
  - Desktop devices -> Receiver mode (base language)
- **Role-Based UI**: Different chat bubble styles for each participant
- **Claude AI Integration**: Provides high-quality translations through the Claude API

## Setup Instructions

1. Install dependencies:
```
npm install
```

2. Start the server:
```
node server.js
```

3. Access the app at http://localhost:3000

## How to Use

1. **Settings Configuration**: 
   - Click the gear icon (⚙️) to open settings
   - Enter your Claude API key
   - Select your preferred languages
   - Choose user mode (customer or receiver)

2. **Chat Flow**:
   - **Customer** (foreign language) writes a message → Claude translates to base language
   - **Receiver** (base language) reads translation, responds → Claude translates to foreign language
   - Claude provides translations in both directions

## Technical Overview

- **Frontend**: HTML, CSS, JavaScript (vanilla)
- **Backend**: Node.js with Express
- **API**: Claude by Anthropic for translation
- **Data Storage**: Local storage for chat history and settings

## Requirements

- Node.js
- A Claude API key from Anthropic