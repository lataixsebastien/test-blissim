export default function ErrorNotice({ message, onRetry }) {
  return (
    <div className="error-notice" role="alert">
      <p>{message}</p>
      {onRetry && (
        <button type="button" className="button secondary" onClick={onRetry}>
          Réessayer
        </button>
      )}
    </div>
  );
}
