import { useState } from "react";

export default function ProductImage({ src, title, eager = false }) {
  const [failed, setFailed] = useState(false);
  return (
    <div className="product-image">
      {failed ? (
        <span
          className="image-placeholder"
          role="img"
          aria-label={`Image indisponible : ${title}`}
        >
          Image indisponible
        </span>
      ) : (
        <img
          src={src}
          alt={title}
          loading={eager ? "eager" : "lazy"}
          onError={() => setFailed(true)}
        />
      )}
    </div>
  );
}
