const searchForm = document.querySelector(".nav-search");

if (searchForm) {
    const searchInput = searchForm.querySelector('input[type="search"]');
    const searchStatus = searchForm.querySelector(".search-status");
    const cards = Array.from(document.querySelectorAll(".t-card, .pcards"));

    const filterCards = () => {
        const query = searchInput.value.trim().toLocaleLowerCase();
        let visibleCount = 0;

        cards.forEach((card) => {
            const matches = card.textContent.toLocaleLowerCase().includes(query);
            card.hidden = !matches;
            visibleCount += Number(matches);
        });

        document.querySelectorAll(".terbaru-section, .genre-section").forEach((section) => {
            const sectionCards = section.querySelectorAll(".t-card");
            section.hidden = query.length > 0
                && sectionCards.length > 0
                && !Array.from(sectionCards).some((card) => !card.hidden);
        });

        if (searchStatus) {
            searchStatus.textContent = !query
                ? ""
                : visibleCount === 0
                    ? "Tidak ada hasil yang cocok."
                    : `${visibleCount} hasil ditemukan.`;
        }
    };

    searchInput.addEventListener("input", filterCards);
    searchForm.addEventListener("submit", (event) => {
        event.preventDefault();
        filterCards();
    });
}
