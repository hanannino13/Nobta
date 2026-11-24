import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it } from 'vitest';

import App from './App';

afterEach(cleanup);

describe('App', () => {
  it('renders the admin dashboard heading', () => {
    render(<App />);

    expect(screen.getByRole('heading', { level: 1 }).textContent).toBe('NOBTA Admin');
  });
});
