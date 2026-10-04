import React from 'react';
import { createRoot } from 'react-dom/client';
import AccountApp from './AccountApp.jsx';
import './styles.css';
import './account.css';

createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <AccountApp />
  </React.StrictMode>,
);
