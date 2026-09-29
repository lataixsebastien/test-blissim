import { useEffect, useState } from "react";
import { api, errorMessage } from "../services/api";
import CommentForm from "./CommentForm";
import ErrorNotice from "./ErrorNotice";

export default function Comments({ productId, canCreate }) {
  const [comments, setComments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");
  const [reload, setReload] = useState(0);

  useEffect(() => {
    const controller = new AbortController();
    setLoading(true);
    setError("");
    api
      .comments(productId, controller.signal)
      .then(setComments)
      .catch((e) => {
        if (!controller.signal.aborted) setError(errorMessage(e));
      })
      .finally(() => {
        if (!controller.signal.aborted) setLoading(false);
      });
    return () => controller.abort();
  }, [productId, reload]);

  async function create(input) {
    const comment = await api.createComment({ product_id: productId, ...input });
    setComments((current) => [comment, ...current]);
    setNotice("Votre commentaire a été ajouté.");
  }

  return (
    <section className="comments-section" aria-labelledby="comments-title">
      <div className="section-heading">
        <div>
          <p className="eyebrow">La conversation</p>
          <h2 id="comments-title">Vos commentaires</h2>
        </div>
        {!loading && !error && (
          <span className="count-label">
            {comments.length} commentaire{comments.length !== 1 ? "s" : ""}
          </span>
        )}
      </div>
      <p role="status" className="success-notice">
        {notice}
      </p>
      <div className="comments-layout">
        <div>
          {loading ? (
            <p role="status">Chargement des commentaires…</p>
          ) : error ? (
            <ErrorNotice
              message={error}
              onRetry={() => setReload((v) => v + 1)}
            />
          ) : comments.length === 0 ? (
            <div className="empty-comments">
              <h3>La discussion commence avec vous.</h3>
              <p>
                Aucun commentaire pour le moment. Partagez votre avis sur ce
                produit.
              </p>
            </div>
          ) : (
            <ul className="comment-list">
              {comments.map((comment) => (
                <CommentCard
                  key={comment.id}
                  comment={comment}
                  onUpdate={(updated) => {
                    setComments((current) =>
                      current.map((item) =>
                        item.id === updated.id ? updated : item,
                      ),
                    );
                    setNotice("Le commentaire a été modifié.");
                  }}
                  onDelete={() => {
                    setComments((current) =>
                      current.filter((item) => item.id !== comment.id),
                    );
                    setNotice("Le commentaire a été supprimé.");
                  }}
                />
              ))}
            </ul>
          )}
        </div>
        <aside className="comment-compose">
          <h3>Partagez votre avis</h3>
          {canCreate && !error && !loading ? (
            <CommentForm onSubmit={create} />
          ) : (
            <p>
              Le formulaire sera disponible une fois le produit et les
              commentaires chargés.
            </p>
          )}
        </aside>
      </div>
    </section>
  );
}

function CommentCard({ comment, onUpdate, onDelete }) {
  const [editing, setEditing] = useState(false);
  const [confirmDelete, setConfirmDelete] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  async function update(input) {
    const updated = await api.updateComment(comment.id, input);
    onUpdate(updated);
    setEditing(false);
  }
  async function remove() {
    setBusy(true);
    setError("");
    try {
      await api.deleteComment(comment.id);
      onDelete();
    } catch (failure) {
      setError(errorMessage(failure));
      setBusy(false);
    }
  }
  return (
    <li className="comment-card">
      {editing ? (
        <CommentForm
          initial={comment}
          onSubmit={update}
          onCancel={() => setEditing(false)}
        />
      ) : (
        <>
          <div className="comment-heading">
            <span className="avatar" aria-hidden="true">
              {comment.author.slice(0, 1).toUpperCase()}
            </span>
            <div>
              <strong>{comment.author}</strong>
              <time dateTime={comment.createdAt}>
                {new Intl.DateTimeFormat("fr-FR", {
                  dateStyle: "medium",
                }).format(new Date(comment.createdAt))}
                {comment.updatedAt && " · modifié"}
              </time>
            </div>
          </div>
          <p className="comment-content">{comment.content}</p>
          {error && <ErrorNotice message={error} />}
          {confirmDelete ? (
            <div className="delete-confirmation">
              <p>Supprimer ce commentaire ?</p>
              <button
                className="text-button danger"
                disabled={busy}
                onClick={remove}
              >
                {busy ? "Suppression…" : "Confirmer la suppression"}
              </button>
              <button
                className="text-button"
                disabled={busy}
                onClick={() => setConfirmDelete(false)}
              >
                Annuler
              </button>
            </div>
          ) : (
            <div className="comment-actions">
              <button className="text-button" onClick={() => setEditing(true)}>
                Modifier
              </button>
              <button
                className="text-button danger"
                onClick={() => setConfirmDelete(true)}
              >
                Supprimer
              </button>
            </div>
          )}
        </>
      )}
    </li>
  );
}
