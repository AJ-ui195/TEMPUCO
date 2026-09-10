<style>
    .ll-page {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .ll-field {
        display: flex;
        flex-direction: column;
        gap: 0.375rem;
        max-width: 28rem;
        font-size: 0.8125rem;
        font-weight: 600;
    }

    .ll-field select {
        width: 100%;
        border-radius: 0.5rem;
        border: 1px solid rgba(148, 163, 184, 0.45);
        background: transparent;
        padding: 0.55rem 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .ll-sheet {
        background: #fff;
        color: #111;
        border: 1px solid #d6d3d1;
        padding: 1.25rem;
        overflow: auto;
    }

    .ll-sheet-head {
        text-align: center;
        color: #111;
        line-height: 1.25;
        margin-bottom: 1rem;
    }

    .ll-head-coop {
        font-size: 0.62rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        margin: 0;
    }

    .ll-head-school {
        font-size: 1.05rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.01em;
        margin: 0.15rem 0 0;
    }

    .ll-head-city {
        font-family: "Times New Roman", Times, Georgia, serif;
        font-size: 0.78rem;
        font-weight: 400;
        text-transform: uppercase;
        margin: 0.1rem 0 0.35rem;
    }

    .ll-head-title,
    .ll-head-subtitle {
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        margin: 0.1rem 0 0;
    }

    .ll-member {
        display: grid;
        gap: 0.35rem;
        margin-bottom: 0.75rem;
        font-size: 0.8rem;
    }

    .ll-member span {
        font-weight: 700;
    }

    .ll-member-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.35rem 1.25rem;
    }

    .ll-table-wrap {
        overflow-x: auto;
    }

    .ll-grid {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.75rem;
    }

    .ll-grid th,
    .ll-grid td {
        border: 1px solid #111;
        padding: 0.2rem 0.35rem;
        vertical-align: top;
    }

    .ll-grid th {
        font-size: 0.68rem;
        text-align: center;
    }

    .ll-grid td.num {
        text-align: right;
        white-space: nowrap;
    }
</style>
