import { useId, useState } from "react";
import { ApiError, errorMessage } from "../services/api";
import ErrorNotice from "./ErrorNotice";

export default function CommentForm({ initial, onSubmit, onCancel }) {
  const id = useId();
  const [author, setAuthor] = useState(initial?.author ?? "");
  const [content, setContent] = useState(initial?.content ?? "");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [details, setDetails] = useState({});

  async function submit(event) {
    event.preventDefault();
    if (busy) return;
    setBusy(true);
    setError("");
    setDetails({});
    try {
      await onSubmit({ author: author.trim(), content: content.trim() });
      if (!initial) {
        setContent("");
      }
    } catch (failure) {
      setError(errorMessage(failure));
      if (failure instanceof ApiError) setDetails(failure.details);
    } finally {
      setBusy(false);
    }
  }

  return (
    <form
      className="comment-form"
      onSubmit={submit}
      aria-label={
        initial ? "Modifier le commentaire" : "Ajouter un commentaire"
      }
    >
      {error && <ErrorNotice message={error} />}
      <fieldset disabled={busy}>
        <div className="field">
          <label htmlFor={`${id}-author`}>Votre nom</label>
          <input
            id={`${id}-author`}
            name="author"
            value={author}
            onChange={(e) => setAuthor(e.target.value)}
            required
            maxLength={120}
            autoComplete="name"
            aria-invalid={!!details.author}
            aria-describedby={details.author ? `${id}-author-error` : undefined}
          />
          {details.author && (
            <span className="field-error" id={`${id}-author-error`}>
              {details.author.join(" ")}
            </span>
          )}
        </div>
        <div className="field">
          <label htmlFor={`${id}-content`}>Votre commentaire</label>
          <textarea
            id={`${id}-content`}
            name="content"
            value={content}
            onChange={(e) => setContent(e.target.value)}
            required
            maxLength={2000}
            rows={4}
            aria-invalid={!!details.content}
            aria-describedby={`${id}-count${details.content ? ` ${id}-content-error` : ""}`}
          />
          <span className="field-hint" id={`${id}-count`}>
            {content.length} / 2 000 caractères
          </span>
          {details.content && (
            <span className="field-error" id={`${id}-content-error`}>
              {details.content.join(" ")}
            </span>
          )}
        </div>
        <div className="form-actions">
          <button className="button" type="submit">
            {busy
              ? "Enregistrement…"
              : initial
                ? "Enregistrer"
                : "Publier le commentaire"}
          </button>
          {onCancel && (
            <button
              className="button secondary"
              type="button"
              onClick={onCancel}
            >
              Annuler
            </button>
          )}
        </div>
      </fieldset>
    </form>
  );
}
