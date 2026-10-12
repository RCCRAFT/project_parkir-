export const useForgotPassword = () => {
  const config = useRuntimeConfig();
  const base = config.public.apiBase;

  const requestOtp = (phone) =>
    $fetch(`${base}/forgot-password/request-otp`, {
      method: 'POST',
      body: { phone },
    });

  const verifyOtp = (phone, otp) =>
    $fetch(`${base}/forgot-password/verify-otp`, {
      method: 'POST',
      body: { phone, otp },
    });

  const resetPassword = (phone, reset_token, password, password_confirmation) =>
    $fetch(`${base}/forgot-password/reset`, {
      method: 'POST',
      body: { phone, reset_token, password, password_confirmation },
    });

  return { requestOtp, verifyOtp, resetPassword };
};