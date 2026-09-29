import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { BrowserRouter } from "react-router";
import App from "./App";
import "./styles.css";

const legacyPath = window.location.hash.match(/^#(\/(?:catalogue|products\/[1-9][0-9]{0,8})?)$/)?.[1];
if (legacyPath) window.history.replaceState(null, "", legacyPath);

createRoot(document.getElementById("root")).render(
  <StrictMode>
    <BrowserRouter>
      <App />
    </BrowserRouter>
  </StrictMode>,
);
